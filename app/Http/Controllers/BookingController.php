<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
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
    public function index()
    {
        $this->expirePendingHolds();

        $showtimes = Showtime::with([
            'movie',
            'room.cinema',
            'format',
        ])
            ->where('status', 'scheduled')
            ->where('starts_at', '>', now())
            ->whereHas('movie', fn ($query) => $query->where('status', '!=', 'inactive'))
            ->whereHas('format', fn ($query) => $query->where('is_active', true))
            ->whereHas('room', fn ($query) => $query->where('is_active', true)
                ->whereHas('cinema', fn ($cinema) => $cinema->where('is_active', true)))
            ->orderBy('starts_at')
            ->paginate(12);

        return view('bookings.index', compact('showtimes'));
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