<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:today,7d,30d,month,custom'],
            'from' => ['nullable', 'date', 'required_if:period,custom'],
            'to' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:from'],
            'cinema_id' => ['nullable', 'integer', 'exists:cinemas,id'],
        ]);
        $period = $filters['period'] ?? '7d';
        $today = now()->startOfDay();
        $rangeEnd = now()->endOfDay();
        $rangeStart = match ($period) {
            'today' => $today->copy(),
            '30d' => $today->copy()->subDays(29),
            'month' => $today->copy()->startOfMonth(),
            'custom' => Carbon::parse($filters['from'])->startOfDay(),
            default => $today->copy()->subDays(6),
        };

        if ($period === 'custom') {
            $rangeEnd = Carbon::parse($filters['to'])->endOfDay();
            if ($rangeStart->diffInDays($rangeEnd) > 92) {
                throw ValidationException::withMessages([
                    'to' => 'Khoảng thời gian báo cáo không được vượt quá 93 ngày.',
                ]);
            }
        }

        $cinemaId = $filters['cinema_id'] ?? null;
        $paidBookings = Booking::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->when($cinemaId, fn ($query) => $query->whereHas(
                'showtime.room',
                fn ($room) => $room->where('cinema_id', $cinemaId)
            ));

        $revenueByDay = (clone $paidBookings)
            ->whereBetween('paid_at', [$rangeStart, $rangeEnd])
            ->selectRaw('DATE(paid_at) as revenue_date, SUM(total_amount) as revenue')
            ->groupByRaw('DATE(paid_at)')
            ->pluck('revenue', 'revenue_date');
        $revenueTrend = collect(CarbonPeriod::create($rangeStart->toDateString(), $rangeEnd->toDateString()))
            ->map(fn ($date) => [
                'date' => $date,
                'amount' => (float) ($revenueByDay[$date->toDateString()] ?? 0),
            ]);
        $maxDailyRevenue = max(1, (float) $revenueTrend->max('amount'));

        $todayStart = $today->copy();
        $todayEnd = $today->copy()->endOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $revenueToday = (clone $paidBookings)
            ->whereBetween('paid_at', [$todayStart, $todayEnd])
            ->sum('total_amount');
        $revenueMonth = (clone $paidBookings)
            ->whereBetween('paid_at', [$monthStart, $todayEnd])
            ->sum('total_amount');
        $ticketsToday = \App\Models\BookingSeat::query()
            ->whereHas('booking', fn ($query) => $query
                ->where('payment_status', 'paid')
                ->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$todayStart, $todayEnd]))
            ->whereHas('showtime.room', fn ($query) => $query->when(
                $cinemaId,
                fn ($room) => $room->where('cinema_id', $cinemaId)
            ))
            ->count();

        $todayShowtimes = Showtime::query()
            ->where('status', 'scheduled')
            ->whereBetween('starts_at', [$todayStart, $todayEnd])
            ->whereHas('room', fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('cinema', fn ($cinema) => $cinema->where('is_active', true)))
            ->when($cinemaId, fn ($query) => $query->whereHas(
                'room',
                fn ($room) => $room->where('cinema_id', $cinemaId)
            ))
            ->with(['movie', 'room.cinema'])
            ->with(['room' => fn ($query) => $query->withCount([
                'seats as active_seats_count' => fn ($seats) => $seats->where('is_active', true),
            ])])
            ->withCount([
                'bookingSeats as occupied_seats_count' => fn ($query) => $query->whereHas(
                    'booking',
                    fn ($booking) => $booking->where(function ($booking) {
                        $booking->where(function ($paid) {
                            $paid->where('status', 'confirmed')
                                ->where('payment_status', 'paid');
                        })->orWhere(function ($held) {
                            $held->where('status', 'pending')
                                ->where('payment_status', 'pending')
                                ->where('hold_expires_at', '>', now());
                        });
                    })
                ),
            ])
            ->orderBy('starts_at')
            ->get();

        $seatCapacityToday = $todayShowtimes->sum(fn ($showtime) => $showtime->room?->active_seats_count ?? 0);
        $occupiedSeatsToday = $todayShowtimes->sum('occupied_seats_count');
        $stats = [
            'revenue_today' => (float) $revenueToday,
            'revenue_month' => (float) $revenueMonth,
            'tickets_today' => $ticketsToday,
            'bookings_today' => Booking::whereBetween('created_at', [$todayStart, $todayEnd])
                ->when($cinemaId, fn ($query) => $query->whereHas(
                    'showtime.room',
                    fn ($room) => $room->where('cinema_id', $cinemaId)
                ))
                ->count(),
            'movies_now_showing' => Movie::where('status', 'now_showing')->count(),
            'showtimes_today' => $todayShowtimes->count(),
            'customers' => User::where('role', 'customer')->count(),
            'occupancy' => $seatCapacityToday > 0
                ? min(100, round($occupiedSeatsToday / $seatCapacityToday * 100, 1))
                : 0,
            'period_revenue' => (float) (clone $paidBookings)
                ->whereBetween('paid_at', [$rangeStart, $rangeEnd])
                ->sum('total_amount'),
        ];

        $recentBookings = Booking::with(['user', 'showtime.movie', 'showtime.room.cinema'])
            ->latest()
            ->limit(8)
            ->get();

        $upcomingShowtimes = $todayShowtimes
            ->filter(fn ($showtime) => $showtime->starts_at->isFuture())
            ->take(6);
        $roomsWithoutSeats = Room::where('is_active', true)->whereDoesntHave('seats')->count();
        $cinemas = Cinema::where('is_active', true)->orderBy('name')->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentBookings',
            'upcomingShowtimes',
            'roomsWithoutSeats',
            'cinemas',
            'revenueTrend',
            'maxDailyRevenue',
            'period',
            'rangeStart',
            'rangeEnd',
            'cinemaId'
        ));
    }
}
