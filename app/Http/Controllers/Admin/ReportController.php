<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSeat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $cinemaId] = $this->filters($request);
        $paidBookings = $this->paidBookings($from, $to, $cinemaId);
        $bookingCount = (clone $paidBookings)->count();
        $revenue = (float) (clone $paidBookings)->sum('total_amount');
        $tickets = BookingSeat::query()
            ->whereHas('booking', fn ($query) => $query
                ->where('payment_status', 'paid')
                ->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$from, $to])
                ->when($cinemaId, fn ($query) => $query->whereHas(
                    'showtime.room',
                    fn ($room) => $room->where('cinema_id', $cinemaId)
                )))
            ->count();

        $dailyRevenue = $this->baseRevenueRows($from, $to, $cinemaId)
            ->selectRaw('DATE(bookings.paid_at) as report_date, SUM(bookings.total_amount) as revenue')
            ->groupByRaw('DATE(bookings.paid_at)')
            ->orderBy('report_date')
            ->get()
            ->keyBy('report_date');
        $trend = collect(\Carbon\CarbonPeriod::create($from->toDateString(), $to->toDateString()))
            ->map(fn ($date) => [
                'date' => $date,
                'revenue' => (float) ($dailyRevenue[$date->toDateString()]->revenue ?? 0),
            ]);

        $cinemaRevenue = $this->baseRevenueRows($from, $to)
            ->join('cinemas', 'rooms.cinema_id', '=', 'cinemas.id')
            ->when($cinemaId, fn ($query) => $query->where('cinemas.id', $cinemaId))
            ->select('cinemas.id', 'cinemas.name')
            ->selectRaw('SUM(bookings.total_amount) as revenue, COUNT(bookings.id) as paid_bookings')
            ->groupBy('cinemas.id', 'cinemas.name')
            ->orderByDesc('revenue')
            ->get();

        $movieRevenue = $this->baseRevenueRows($from, $to, $cinemaId)
            ->join('movies', 'showtimes.movie_id', '=', 'movies.id')
            ->select('movies.id', 'movies.title')
            ->selectRaw('SUM(bookings.total_amount) as revenue, COUNT(bookings.id) as paid_bookings')
            ->groupBy('movies.id', 'movies.title')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $paymentMethods = (clone $paidBookings)
            ->selectRaw("COALESCE(payment_method, 'Chưa ghi nhận') as method, COUNT(*) as transactions, SUM(total_amount) as revenue")
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();
        $cinemas = \App\Models\Cinema::where('is_active', true)->orderBy('name')->get();
        $maxDailyRevenue = max(1, (float) $trend->max('revenue'));

        return view('admin.reports.index', compact(
            'from',
            'to',
            'cinemaId',
            'cinemas',
            'revenue',
            'bookingCount',
            'tickets',
            'trend',
            'cinemaRevenue',
            'movieRevenue',
            'paymentMethods',
            'maxDailyRevenue'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $cinemaId] = $this->filters($request);
        $rows = $this->baseRevenueRows($from, $to)
            ->join('cinemas', 'rooms.cinema_id', '=', 'cinemas.id')
            ->when($cinemaId, fn ($query) => $query->where('cinemas.id', $cinemaId))
            ->select('cinemas.name')
            ->selectRaw('SUM(bookings.total_amount) as revenue, COUNT(bookings.id) as paid_bookings')
            ->groupBy('cinemas.id', 'cinemas.name')
            ->orderByDesc('revenue')
            ->get();

        return response()->streamDownload(function () use ($rows, $from, $to) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Không thể tạo tệp báo cáo.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Báo cáo doanh thu YUNFLIMS', $from->format('d/m/Y'), $to->format('d/m/Y')]);
            fputcsv($output, ['Cụm rạp', 'Đơn thanh toán thành công', 'Doanh thu (VND)']);
            foreach ($rows as $row) {
                fputcsv($output, [
                    $this->safeCsvCell($row->name),
                    (int) $row->paid_bookings,
                    (float) $row->revenue,
                ]);
            }

            fclose($output);
        }, 'yunflims-revenue-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'cinema_id' => ['nullable', 'integer', 'exists:cinemas,id'],
        ]);
        $from = isset($data['from'])
            ? Carbon::parse($data['from'])->startOfDay()
            : now()->startOfMonth();
        $to = isset($data['to'])
            ? Carbon::parse($data['to'])->endOfDay()
            : now()->endOfDay();

        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages([
                'to' => 'Khoảng thời gian báo cáo không được vượt quá 367 ngày.',
            ]);
        }

        return [$from, $to, $data['cinema_id'] ?? null];
    }

    private function paidBookings(Carbon $from, Carbon $to, ?int $cinemaId): Builder
    {
        return Booking::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->when($cinemaId, fn ($query) => $query->whereHas(
                'showtime.room',
                fn ($room) => $room->where('cinema_id', $cinemaId)
            ));
    }

    private function baseRevenueRows(Carbon $from, Carbon $to, ?int $cinemaId = null)
    {
        return DB::table('bookings')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->join('rooms', 'showtimes.room_id', '=', 'rooms.id')
            ->where('bookings.payment_status', 'paid')
            ->whereNotNull('bookings.paid_at')
            ->whereBetween('bookings.paid_at', [$from, $to])
            ->when($cinemaId, fn ($query) => $query->where('rooms.cinema_id', $cinemaId));
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^[\s]*[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
    }
}
