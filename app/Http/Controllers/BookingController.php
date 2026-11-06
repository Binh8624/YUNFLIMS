<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cinema;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Services\ShowtimeRules;
use App\Services\TicketPriceCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $this->expirePendingHolds();

        $today = now()->startOfDay();
        $maxDate = $today->copy()->addDays(6);

        // Tạo danh sách 7 ngày tiếp theo tính từ hôm nay
        $days = collect(range(0, 6))->map(function (int $offset) use ($today) {
            $day = $today->copy()->addDays($offset);

            return [
                'date' => $day->toDateString(),
                'day' => $day->format('d'),
                'month' => $day->format('m'),
                'date_formatted' => $day->format('d/m'),
                'day_of_week' => match ($day->dayOfWeek) {
                    0 => 'Chủ Nhật',
                    1 => 'Thứ Hai',
                    2 => 'Thứ Ba',
                    3 => 'Thứ Tư',
                    4 => 'Thứ Năm',
                    5 => 'Thứ Sáu',
                    6 => 'Thứ Bảy',
                },
                'day_of_week_short' => match ($day->dayOfWeek) {
                    0 => 'CN',
                    1 => 'T2',
                    2 => 'T3',
                    3 => 'T4',
                    4 => 'T5',
                    5 => 'T6',
                    6 => 'T7',
                },
                'is_today' => $offset === 0,
            ];
        });

        // Kiểm tra và chuẩn hóa ngày được chọn
        $requestedDate = $request->query('date');
        $selectedDate = $today->toDateString();
        if ($requestedDate && is_string($requestedDate)) {
            try {
                $parsed = Carbon::createFromFormat('Y-m-d', $requestedDate)->startOfDay();
                if ($parsed->greaterThanOrEqualTo($today) && $parsed->lessThanOrEqualTo($maxDate)) {
                    $selectedDate = $parsed->toDateString();
                }
            } catch (\Exception $e) {
                $selectedDate = $today->toDateString();
            }
        }

        $cinemaId = $request->filled('cinema_id') ? (int) $request->query('cinema_id') : null;
        $movieId = $request->filled('movie_id') ? (int) $request->query('movie_id') : null;

        $dayStart = Carbon::parse($selectedDate)->startOfDay();
        $dayEnd = $dayStart->copy()->endOfDay();
        $isToday = $dayStart->isToday();

        // Truy vấn suất chiếu hợp lệ cho ngày được chọn
        $showtimeQuery = Showtime::with([
            'movie.formats' => fn ($query) => $query->where('formats.is_active', true),
            'room.cinema',
            'room.formats' => fn ($query) => $query->where('formats.is_active', true),
            'format',
        ])
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', $isToday ? now() : $dayStart)
            ->where('starts_at', '<=', $dayEnd)
            ->whereHas('movie', fn ($query) => $query->where('status', '!=', 'inactive'))
            ->whereHas('format', fn ($query) => $query->where('is_active', true))
            ->whereHas('room', fn ($query) => $query->where('is_active', true)
                ->whereHas('cinema', fn ($cinema) => $cinema->where('is_active', true))
            )
            ->whereHas('movie.formats', fn ($format) => $format->whereColumn('formats.id', 'showtimes.format_id'))
            ->whereHas('room.formats', fn ($format) => $format->whereColumn('formats.id', 'showtimes.format_id'))
            ->orderBy('starts_at');

        if ($cinemaId) {
            $showtimeQuery->whereHas('room.cinema', fn ($query) => $query->where('cinemas.id', $cinemaId));
        }

        if ($movieId) {
            $showtimeQuery->where('movie_id', $movieId);
        }

        $showtimes = $showtimeQuery->get();

        // Gom nhóm suất chiếu theo Phim -> Rạp -> Phòng & Định dạng
        $groupedByMovie = $showtimes->groupBy('movie_id')->map(function ($movieShowtimes) {
            $movie = $movieShowtimes->first()->movie;
            $cinemas = $movieShowtimes->groupBy(fn ($showtime) => $showtime->room->cinema_id)->map(function ($cinemaShowtimes) {
                $cinema = $cinemaShowtimes->first()->room->cinema;
                $rooms = $cinemaShowtimes->groupBy(fn ($showtime) => $showtime->room_id . '-' . $showtime->format_id)->map(function ($roomShowtimes) {
                    $first = $roomShowtimes->first();

                    return [
                        'room' => $first->room,
                        'format' => $first->format,
                        'showtimes' => $roomShowtimes,
                    ];
                });

                return [
                    'cinema' => $cinema,
                    'rooms' => $rooms,
                ];
            });

            return [
                'movie' => $movie,
                'cinemas' => $cinemas,
                'total_showtimes' => $movieShowtimes->count(),
            ];
        });

        // Danh sách rạp và phim đang hoạt động phục vụ bộ lọc
        $cinemas = Cinema::where('is_active', true)->orderBy('name')->get();
        $movies = Movie::where('status', '!=', 'inactive')->orderBy('title')->get();

        return view('bookings.index', compact(
            'days',
            'selectedDate',
            'groupedByMovie',
            'cinemas',
            'movies',
            'cinemaId',
            'movieId'
        ));
    }

    public function history()
    {
        $this->expirePendingHolds();

        $bookings = auth()->user()
            ->bookings()
            ->with([
                'showtime.movie',
                'showtime.room.cinema',
                'bookingSeats.seat',
            ])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('bookings.history', compact('bookings'));
    }

    public function selectSeats(Showtime $showtime)
    {
        $this->expirePendingHolds();
        $showtime->load(['movie.formats', 'room.cinema', 'room.formats', 'format']);

        abort_unless(
            $showtime->status === 'scheduled'
            && $showtime->starts_at->isFuture(),
            404
        );
        abort_unless(
            $showtime->format?->is_active
            && $showtime->room->is_active
            && $showtime->room->cinema?->is_active
            && ShowtimeRules::supportsFormat(
                $showtime->movie->formats->pluck('id')->all(),
                $showtime->room->formats->pluck('id')->all(),
                (int) $showtime->format_id
            ),
            404
        );

        $seats = Seat::where('room_id', $showtime->room_id)
            ->orderBy('row_number')
            ->orderBy('seat_number')
            ->get();

        $bookedSeatIds = BookingSeat::where('showtime_id', $showtime->id)
            ->whereHas('booking', function ($query) {
                $query->where('status', 'confirmed')
                    ->orWhere(function ($query) {
                        $query->where('status', 'pending')
                            ->where('hold_expires_at', '>', now());
                    });
            })
            ->pluck('seat_id')
            ->all();

        $priceMap = app(TicketPriceCalculator::class)->pricesForSeats($showtime, $seats);

        return view(
            'bookings.seats',
            compact('showtime', 'seats', 'bookedSeatIds', 'priceMap')
        );
    }

    public function store(
        Request $request,
        Showtime $showtime,
        TicketPriceCalculator $priceCalculator
    )
    {
        $validated = $request->validate([
            'seats' => ['required', 'array', 'min:1', 'max:10'],
            'seats.*' => ['required', 'integer', 'distinct', 'exists:seats,id'],
        ], [
            'seats.required' => 'Vui lòng chọn ghế.',
            'seats.min' => 'Vui lòng chọn ít nhất một ghế.',
            'seats.max' => 'Mỗi lần chỉ được đặt tối đa 10 ghế.',
            'seats.*.distinct' => 'Ghế được chọn không được trùng nhau.',
        ]);

        try {
            $booking = DB::transaction(function () use (
                $validated,
                $showtime,
                $request,
                $priceCalculator
            ) {
                $lockedShowtime = Showtime::whereKey($showtime->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedShowtime->status !== 'scheduled'
                    || $lockedShowtime->starts_at->isPast()
                ) {
                    throw ValidationException::withMessages([
                        'seats' => 'Suất chiếu này không còn nhận đặt vé.',
                    ]);
                }

                $room = Room::whereKey($lockedShowtime->room_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $movie = Movie::whereKey($lockedShowtime->movie_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->load('formats');
                $lockedShowtime->load('format');
                $room->load(['cinema', 'formats']);
                if (
                    ! $room->is_active
                    || ! $room->cinema?->is_active
                    || ! $lockedShowtime->format?->is_active
                    || $movie->status === 'inactive'
                    || ! ShowtimeRules::supportsFormat(
                        $movie->formats->pluck('id')->all(),
                        $room->formats->pluck('id')->all(),
                        (int) $lockedShowtime->format_id
                    )
                ) {
                    throw ValidationException::withMessages([
                        'seats' => 'Suất chiếu không còn hợp lệ hoặc phòng không hỗ trợ định dạng này.',
                    ]);
                }

                Booking::where('showtime_id', $lockedShowtime->id)
                    ->where('status', 'pending')
                    ->whereNotNull('hold_expires_at')
                    ->where('hold_expires_at', '<=', now())
                    ->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancelled',
                    ]);

                $seats = Seat::whereIn('id', $validated['seats'])
                    ->where('room_id', $lockedShowtime->room_id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->get();

                if ($seats->count() !== count($validated['seats'])) {
                    throw ValidationException::withMessages([
                        'seats' => 'Có ghế không hợp lệ hoặc không thuộc phòng chiếu này.',
                    ]);
                }

                if ($seats->contains(fn (Seat $seat) => ! $seat->is_active)) {
                    throw ValidationException::withMessages([
                        'seats' => 'Một hoặc nhiều ghế đã bị khóa hoặc hỏng.',
                    ]);
                }

                $alreadyBooked = BookingSeat::where(
                    'showtime_id',
                    $lockedShowtime->id
                )
                    ->whereIn('seat_id', $validated['seats'])
                    ->whereHas('booking', function ($query) {
                        $query->where('status', 'confirmed')
                            ->orWhere(function ($query) {
                                $query->where('status', 'pending')
                                    ->where('hold_expires_at', '>', now());
                            });
                    })
                    ->exists();

                if ($alreadyBooked) {
                    throw ValidationException::withMessages([
                        'seats' => 'Một hoặc nhiều ghế vừa được người khác đặt. Vui lòng chọn lại.',
                    ]);
                }

                $bookingCode = Booking::generateBookingCode();
                $seatPrices = $priceCalculator->pricesForSeats($lockedShowtime, $seats);
                $booking = Booking::create([
                    'user_id' => $request->user()->id,
                    'showtime_id' => $lockedShowtime->id,
                    'booking_code' => $bookingCode,
                    'total_amount' => $seatPrices->sum(),
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'hold_expires_at' => now()->addMinutes(10),
                    'qr_code' => Booking::generateQrSvg($bookingCode),
                ]);

                foreach ($seats as $seat) {
                    BookingSeat::create([
                        'booking_id' => $booking->id,
                        'showtime_id' => $lockedShowtime->id,
                        'seat_id' => $seat->id,
                        'price' => $seatPrices[$seat->id],
                    ]);
                }

                return $booking;
            });

            return redirect()
                ->route('bookings.payment', $booking)
                ->with('success', 'Bạn đã tạo đơn đặt vé. Vui lòng thanh toán để xác nhận vé.');
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'seats' => 'Ghế vừa được đặt bởi người khác. Vui lòng chọn ghế khác.',
                    ]);
            }

            throw $exception;
        }
    }

    public function payment(Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        if ($booking->status !== 'pending') {
            return redirect()->route('bookings.show', $booking);
        }

        if (! $booking->hold_expires_at || $booking->hold_expires_at->isPast()) {
            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

            return redirect()->route('bookings.show', $booking)
                ->withErrors(['payment' => 'Thời gian giữ ghế đã hết. Vui lòng đặt lại ghế.']);
        }

        $booking->load([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
        ]);

        return view('bookings.payment', compact('booking'));
    }

    public function processPayment(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:card,momo,bank_transfer,cash'],
            'test_result' => ['required', 'in:success,failed'],
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'test_result.required' => 'Vui lòng chọn kết quả thanh toán mô phỏng.',
            'test_result.in' => 'Kết quả mô phỏng không hợp lệ.',
        ]);

        $paymentMethod = $validated['payment_method'];
        $result = DB::transaction(function () use ($booking, $paymentMethod, $validated) {
            $lockedShowtime = Showtime::whereKey($booking->showtime_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->status === 'confirmed') {
                return 'confirmed';
            }

            if (
                $lockedShowtime->status !== 'scheduled'
                ||
                $lockedBooking->status !== 'pending'
                || ! $lockedBooking->hold_expires_at
                || $lockedBooking->hold_expires_at->isPast()
            ) {
                $lockedBooking->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                ]);

                return 'expired';
            }

            if ($validated['test_result'] === 'failed') {
                $lockedBooking->update([
                    'payment_status' => 'failed',
                    'payment_method' => $paymentMethod,
                ]);

                return 'failed';
            }

            $lockedBooking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => $paymentMethod,
                'payment_reference' => 'YF-' . strtoupper(\Illuminate\Support\Str::random(16)),
                'paid_at' => now(),
                'hold_expires_at' => null,
            ]);

            return 'paid';
        });

        if ($result === 'paid' || $result === 'confirmed') {
            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Thanh toán mô phỏng thành công. Vé đã được xác nhận.');
        }

        return back()->withErrors([
            'payment_method' => $result === 'expired'
                ? 'Thời gian giữ ghế đã hết. Vui lòng chọn lại ghế.'
                : 'Thanh toán mô phỏng thất bại. Bạn có thể thử lại khi thời gian giữ ghế còn hiệu lực.',
        ]);
    }

    public function show(Booking $booking)
    {
        abort_unless(
            $booking->user_id === auth()->id(),
            403
        );

        $booking->load([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
        ]);

        return view('bookings.show', compact('booking'));
    }

    private function expirePendingHolds(): void
    {
        Booking::where('status', 'pending')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
    }
}