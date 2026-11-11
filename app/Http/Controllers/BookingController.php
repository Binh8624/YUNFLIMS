<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Services\ShowtimeRules;
use App\Services\TicketPriceCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $this->expirePendingHolds();

        $filters = $request->validate([
            'city' => ['nullable', 'string', 'max:100'],
            'cinema_id' => ['nullable', 'integer', 'exists:cinemas,id'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'search' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', 'string', 'max:150'],
        ]);
        $date = $filters['date'] ?? now()->toDateString();
        $dayStart = \Illuminate\Support\Carbon::parse($date)->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        $showtimes = Showtime::query()
            ->with([
                'movie',
                'room.cinema',
                'room' => fn ($room) => $room->withCount([
                    'seats as active_seats_count' => fn ($seats) => $seats
                        ->where('is_active', true),
                ]),
                'format',
            ])
            ->withCount([
                'bookingSeats as unavailable_seats_count' => fn ($bookingSeats) => $bookingSeats
                    ->whereHas('booking', function ($booking) {
                        $booking->where('status', 'confirmed')
                            ->orWhere(function ($booking) {
                                $booking->where('status', 'pending')
                                    ->where('hold_expires_at', '>', now());
                            });
                    }),
            ])
            ->where('status', 'scheduled')
            ->where('starts_at', '>', now())
            ->where('ends_at', '>', now())
            ->where('starts_at', '>=', $dayStart)
            ->where('starts_at', '<', $dayEnd)
            ->whereHas('movie', function ($query) use ($filters) {
                $query->whereIn('status', ['now_showing', 'coming_soon'])
                    ->when(
                        ! empty($filters['search']),
                        fn ($query) => $query->where(
                            'title',
                            'like',
                            '%'.trim($filters['search']).'%'
                        )
                    )
                    ->when(
                        ! empty($filters['genre']),
                        fn ($query) => $query->where('genre', $filters['genre'])
                    );
            })
            ->whereHas('format', fn ($query) => $query->where('is_active', true))
            ->whereHas('movie.formats', fn ($format) => $format
                ->whereColumn('formats.id', 'showtimes.format_id')
                ->where('formats.is_active', true))
            ->whereHas('room.formats', fn ($format) => $format
                ->whereColumn('formats.id', 'showtimes.format_id')
                ->where('formats.is_active', true))
            ->whereHas('room', function ($query) use ($filters) {
                $query->where('is_active', true)
                    ->whereHas('cinema', function ($cinema) use ($filters) {
                        $cinema->where('is_active', true)
                            ->when(
                                ! empty($filters['city']),
                                fn ($cinema) => $cinema->where('city', $filters['city'])
                            )
                            ->when(
                                ! empty($filters['cinema_id']),
                                fn ($cinema) => $cinema->whereKey($filters['cinema_id'])
                            );
                    });
            })
            ->orderBy('starts_at')
            ->paginate(20)
            ->withQueryString();

        $cities = Cinema::where('is_active', true)
            ->whereNotNull('city')
            ->where('city', '<>', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
        $cinemas = Cinema::where('is_active', true)
            ->when(
                ! empty($filters['city']),
                fn ($query) => $query->where('city', $filters['city'])
            )
            ->orderBy('name')
            ->get();
        $genres = Movie::whereHas('showtimes', function ($query) use ($dayStart, $dayEnd) {
            $query->where('status', 'scheduled')
                ->where('starts_at', '>', now())
                ->where('starts_at', '>=', $dayStart)
                ->where('starts_at', '<', $dayEnd);
        })
            ->whereIn('status', ['now_showing', 'coming_soon'])
            ->whereNotNull('genre')
            ->where('genre', '<>', '')
            ->distinct()
            ->orderBy('genre')
            ->pluck('genre');

        return view('bookings.index', compact(
            'showtimes',
            'cities',
            'cinemas',
            'genres',
            'date'
        ));
    }

    public function history(Request $request)
    {
        $this->expirePendingHolds();

        $filters = $request->validate([
            'status' => ['nullable', 'in:upcoming,used,cancelled,pending'],
        ]);
        $query = auth()->user()
            ->bookings()
            ->with([
                'showtime.movie',
                'showtime.room.cinema',
                'bookingSeats.seat',
            ]);

        match ($filters['status'] ?? null) {
            'upcoming' => $query
                ->where('status', 'confirmed')
                ->whereHas('showtime', fn ($showtime) => $showtime->where('ends_at', '>', now())),
            'used' => $query
                ->where('status', 'confirmed')
                ->whereHas('showtime', fn ($showtime) => $showtime->where('ends_at', '<=', now())),
            'cancelled' => $query->where('status', 'cancelled'),
            'pending' => $query->where('status', 'pending'),
            default => $query,
        };

        $bookings = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

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
                    'hold_expires_at' => now()->addMinutes(5),
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

    public function cancel(Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        $cancelled = DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->status !== 'pending') {
                return false;
            }

            $lockedBooking->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

            return true;
        });

        if (! $cancelled) {
            return redirect()->route('bookings.show', $booking)
                ->withErrors(['booking' => 'Đơn đặt vé này không còn ở trạng thái chờ thanh toán.']);
        }

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Đã hủy đơn và giải phóng các ghế đang giữ.');
    }

    public function processPayment(Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        if ($booking->status !== 'pending') {
            return redirect()->route('bookings.show', $booking);
        }

        return back()->withErrors([
            'payment_method' => 'Thanh toán trực tuyến chưa được tích hợp. Đơn này chưa bị trừ tiền hoặc xác nhận vé.',
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