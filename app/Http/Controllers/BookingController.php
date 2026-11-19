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
use App\Services\SingleSeatRule;
use App\Services\TicketPriceCalculator;
use App\Support\MovieGenres;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\ConcessionOrder;
use App\Models\ConcessionProduct;

class BookingController extends Controller
{
    private const MOCK_PAYMENT_METHOD = 'mock_test';

    public function index(Request $request)
    {
        $this->expirePendingHolds();

        $cities = Cinema::query()
            ->where('is_active', true)
            ->whereNotNull('city')
            ->where('city', '<>', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
        $formats = Format::query()
            ->where('is_active', true)
            ->whereIn('slug', ['2d', '3d', 'imax'])
            ->orderBy('id')
            ->get();
        $genres = MovieGenres::options(
            Movie::query()
                ->whereIn('status', ['now_showing', 'coming_soon'])
                ->where('age_rating', '<>', 'C')
                ->pluck('genre')
        );

        $filters = $request->validate([
            'city' => ['nullable', 'string', Rule::in($cities->all())],
            'cinema_id' => ['nullable', 'integer', Rule::exists('cinemas', 'id')->where('is_active', true)],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'search' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', 'string', Rule::in($genres)],
            'format' => ['nullable', 'string', Rule::in($formats->pluck('slug')->all())],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
        ]);
        if (! empty($filters['city']) && ! empty($filters['cinema_id'])
            && ! Cinema::query()->whereKey($filters['cinema_id'])->where('city', $filters['city'])->exists()) {
            throw ValidationException::withMessages([
                'cinema_id' => 'Rạp đã chọn không thuộc thành phố được lọc.',
            ]);
        }

        $baseQuery = $this->customerScheduleQuery($filters);
        $showDates = (clone $baseQuery)
            ->where('starts_at', '>=', now())
            ->selectRaw('DATE(starts_at) as show_date')
            ->distinct()
            ->orderBy('show_date')
            ->pluck('show_date')
            ->map(fn ($showDate) => (string) $showDate)
            ->values();
        $date = $filters['date'] ?? $showDates->first() ?? now()->toDateString();
        $showtimes = (clone $baseQuery)
            ->with([
                'movie.formats' => fn ($query) => $query->where('formats.is_active', true),
                'movie.format',
                'room.cinema',
                'room.formats' => fn ($query) => $query->where('formats.is_active', true),
                'room.format',
                'room' => fn ($room) => $room->withCount([
                    'seats as active_seats_count' => fn ($seats) => $seats->where('is_active', true),
                ]),
                'format',
            ])
            ->withCount([
                'bookingSeats as unavailable_seats_count' => fn ($bookingSeats) => $bookingSeats
                    ->whereHas('booking', fn ($booking) => $booking
                        ->where('status', 'confirmed')
                        ->orWhere(fn ($booking) => $booking
                            ->where('status', 'pending')
                            ->where('hold_expires_at', '>', now())),
                    ),
            ])
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->get();
        $cinemas = Cinema::query()
            ->where('is_active', true)
            ->when(
                ! empty($filters['city']),
                fn ($query) => $query->where('city', $filters['city'])
            )
            ->orderBy('name')
            ->get();
        $selectedMovie = ! empty($filters['movie_id'])
            ? Movie::query()->find($filters['movie_id'])
            : null;
        $hasSelectedMovieShowtimes = $selectedMovie
            ? $this->customerScheduleQuery(['movie_id' => $selectedMovie->getKey()])->exists()
            : false;

        return view('bookings.index', compact(
            'showtimes',
            'cities',
            'cinemas',
            'genres',
            'date',
            'showDates',
            'formats',
            'filters',
            'selectedMovie',
            'hasSelectedMovieShowtimes'
        ));
    }
    

    
private function customerScheduleQuery(array $filters): Builder
{
    return Showtime::query()
        ->where('status', 'scheduled')
        ->where('starts_at', '>', now())
        ->where('ends_at', '>', now())
        ->whereHas('movie', function ($movie) use ($filters) {
            $movie->whereIn('status', ['now_showing', 'coming_soon'])
                ->where('age_rating', '<>', 'C')
                ->when(
                    ! empty($filters['search']),
                    fn ($movie) => $movie->where('title', 'like', '%'.trim($filters['search']).'%')
                )
                ->when(
                    ! empty($filters['movie_id']),
                    fn ($movie) => $movie->whereKey($filters['movie_id'])
                )
                ->when(
                    ! empty($filters['genre']),
                    fn ($movie) => MovieGenres::applyFilter($movie, $filters['genre'])
                )
                ->whereHas('formats', fn ($format) =>
                    $format->whereColumn('formats.id', 'showtimes.format_id')
                );
        })
        ->whereHas('format', function ($format) use ($filters) {
            $format->where('is_active', true)
                ->when(
                    ! empty($filters['format']),
                    fn ($format) => $format->where('slug', $filters['format'])
                );
        })
        ->whereHas('room', function ($room) use ($filters) {
            $room->where('is_active', true)
                ->whereHas('formats', fn ($format) =>
                    $format->whereColumn('formats.id', 'showtimes.format_id')
                )
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
        });
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
        $showtime->load([
    'movie.formats',
    'movie.format',
    'room.cinema',
    'room.formats',
    'room.format',
    'format',
]);

        abort_unless(
            $showtime->status === 'scheduled'
            && $showtime->starts_at->isFuture(),
            404
        );
        abort_unless(
            $showtime->format?->is_active
            && $showtime->movie->status !== 'inactive'
            && $showtime->movie->age_rating !== 'C'
            && $showtime->room->is_active
            && $showtime->room->cinema?->is_active
            && ShowtimeRules::supportsFormat(
    $showtime->movie->formats->pluck('id')->map(fn ($id) => (int) $id)->all(),
    $showtime->room->formats->pluck('id')->map(fn ($id) => (int) $id)->all(),
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
                $query->where('status', 'confirmed');
            })
            ->pluck('seat_id')
            ->all();
        $heldSeatIds = BookingSeat::where('showtime_id', $showtime->id)
            ->whereHas('booking', fn ($query) => $query
                ->where('status', 'pending')
                ->whereNotNull('hold_expires_at')
                ->where('hold_expires_at', '>', now()))
            ->whereNotIn('seat_id', $bookedSeatIds)
            ->pluck('seat_id')
            ->all();

        $priceMap = app(TicketPriceCalculator::class)->pricesForSeats($showtime, $seats);

        return view(
            'bookings.seats',
            compact('showtime', 'seats', 'bookedSeatIds', 'heldSeatIds', 'priceMap')
        );
    }

    public function checkSeatSelection(
        Request $request,
        Showtime $showtime
    ): JsonResponse {
        $validated = $request->validate([
            'seats' => ['present', 'array', 'max:10'],
            'seats.*' => ['required', 'integer', 'distinct', 'exists:seats,id'],
        ]);

        $result = DB::transaction(function () use ($validated, $showtime) {
            $lockedShowtime = Showtime::whereKey($showtime->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedShowtime->load([
    'movie.formats',
    'movie.format',
    'room.cinema',
    'room.formats',
    'room.format',
    'format',
]);

            if (
                $lockedShowtime->status !== 'scheduled'
                || $lockedShowtime->starts_at->isPast()
                || $lockedShowtime->movie->status === 'inactive'
                || $lockedShowtime->movie->age_rating === 'C'
                || ! $lockedShowtime->format?->is_active
                || ! $lockedShowtime->room->is_active
                || ! $lockedShowtime->room->cinema?->is_active
                || ! ShowtimeRules::supportsFormat(
    $lockedShowtime->movie->formats->pluck('id')->map(fn ($id) => (int) $id)->all(),
    $lockedShowtime->room->formats->pluck('id')->map(fn ($id) => (int) $id)->all(),
    (int) $lockedShowtime->format_id
)
            ) {
                abort(404);
            }

            $selectedIds = array_map('intval', $validated['seats']);
            $selectedSeats = Seat::whereIn('id', $selectedIds)
                ->where('room_id', $lockedShowtime->room_id)
                ->lockForUpdate()
                ->get();

            if ($selectedSeats->count() !== count($selectedIds)) {
                return [
                    'valid' => false,
                    'message' => 'Có ghế không thuộc phòng chiếu này. Vui lòng chọn lại.',
                    'unavailableSeats' => [],
                    'isolatedSeatIds' => [],
                ];
            }

            if ($selectedSeats->contains(fn (Seat $seat) => ! $seat->is_active)) {
                return [
                    'valid' => false,
                    'message' => 'Một hoặc nhiều ghế đã ngừng sử dụng.',
                    'unavailableSeats' => [],
                    'isolatedSeatIds' => [],
                ];
            }

            $occupiedSeatIds = $this->occupiedSeatIds($lockedShowtime->id);
            $unavailableIds = array_values(array_intersect($selectedIds, $occupiedSeatIds));
            if ($unavailableIds !== []) {
                $bookedIds = BookingSeat::where('showtime_id', $lockedShowtime->id)
                    ->whereIn('seat_id', $unavailableIds)
                    ->whereHas('booking', fn ($query) => $query->where('status', 'confirmed'))
                    ->pluck('seat_id')
                    ->all();
                $unavailableSeats = [];
                foreach ($unavailableIds as $seatId) {
                    $unavailableSeats[$seatId] = in_array($seatId, $bookedIds, false) ? 'booked' : 'held';
                }

                return [
                    'valid' => false,
                    'message' => 'Một hoặc nhiều ghế vừa được đặt hoặc đang được giữ bởi khách khác.',
                    'unavailableSeats' => $unavailableSeats,
                    'isolatedSeatIds' => [],
                ];
            }

            return ['valid' => true, 'message' => null, 'unavailableSeats' => [], 'isolatedSeatIds' => []];
        });

        return response()->json($result);
    }

    public function store(
        Request $request,
        Showtime $showtime,
        TicketPriceCalculator $priceCalculator,
        SingleSeatRule $singleSeatRule
    ) {
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
                $priceCalculator,
                $singleSeatRule
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
                    ->load(['formats', 'format']);
                $lockedShowtime->load('format');
                $room->load(['cinema', 'formats', 'format']);
                if (
                    ! $room->is_active
                    || ! $room->cinema?->is_active
                    || ! $lockedShowtime->format?->is_active
                    || $movie->status === 'inactive'
                    || $movie->age_rating === 'C'
                    || ! ShowtimeRules::supportsFormat(
    $movie->formats->pluck('id')->map(fn ($id) => (int) $id)->all(),
    $room->formats->pluck('id')->map(fn ($id) => (int) $id)->all(),
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
                    ->whereHas('booking', fn ($query) => $query
                        ->where('status', 'confirmed')
                        ->orWhere(function ($query) {
                            $query->where('status', 'pending')
                                ->whereNotNull('hold_expires_at')
                                ->where('hold_expires_at', '>', now());
                        }))
                    ->exists();

                if ($alreadyBooked) {
                    throw ValidationException::withMessages([
                        'seats' => 'Một hoặc nhiều ghế vừa được người khác đặt. Vui lòng chọn lại.',
                    ]);
                }

                $occupiedSeatIds = $this->occupiedSeatIds($lockedShowtime->id);
                $roomSeats = Seat::where('room_id', $lockedShowtime->room_id)
                    ->orderBy('row_number')
                    ->orderBy('seat_number')
                    ->get();
                if ($singleSeatRule->leavesSingleSeat($roomSeats, $occupiedSeatIds, $validated['seats'])) {
                    throw ValidationException::withMessages([
                        'seats' => 'Không thể đặt các vị trí đã chọn vì sẽ để lại một ghế trống duy nhất. Vui lòng chọn vị trí khác.',
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
                    'ticket_status' => 'available',
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
    ->route('bookings.concessions', $booking)
    ->with('success', 'Bạn đã giữ ghế thành công. Hãy chọn bắp nước hoặc tiếp tục thanh toán.');
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

    public function concessions(Booking $booking)
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
            ->withErrors(['booking' => 'Thời gian giữ ghế đã hết. Vui lòng đặt lại ghế.']);
    }

    $products = ConcessionProduct::query()
        ->where('is_active', true)
        ->where('stock_quantity', '>', 0)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    $booking->load([
    'showtime.movie',
    'showtime.room.cinema',
    'bookingSeats.seat',
    'concessionOrder.items',
]);

    $selectedItems = [];

    if ($booking->concessionOrder) {
        foreach ($booking->concessionOrder->items as $item) {
            if ($item->concession_product_id) {
                $selectedItems[$item->concession_product_id] = $item->quantity;
            }
        }
    }

    return view('bookings.concessions', compact(
        'booking',
        'products',
        'selectedItems'
    ));
}

public function saveConcessions(Request $request, Booking $booking)
{
    abort_unless($booking->user_id === auth()->id(), 403);

    $validated = $request->validate([
        'items' => ['nullable', 'array'],
        'items.*' => ['required', 'integer', 'min:0', 'max:10'],
    ], [
        'items.*.integer' => 'Số lượng sản phẩm không hợp lệ.',
        'items.*.min' => 'Số lượng không được âm.',
        'items.*.max' => 'Mỗi sản phẩm chỉ được chọn tối đa 10.',
    ]);

    try {
        DB::transaction(function () use ($booking, $validated) {
            $lockedBooking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->user_id !== auth()->id()
                || $lockedBooking->status !== 'pending'
                || ! $lockedBooking->hold_expires_at
                || $lockedBooking->hold_expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'items' => 'Booking không còn hợp lệ hoặc thời gian giữ ghế đã hết.',
                ]);
            }

            $quantities = collect($validated['items'] ?? [])
                ->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])
                ->filter(fn ($quantity) => $quantity > 0);

            $products = ConcessionProduct::query()
                ->whereIn('id', $quantities->keys())
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $quantities->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Một hoặc nhiều sản phẩm không còn kinh doanh. Vui lòng tải lại trang.',
                ]);
            }

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if ($product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Sản phẩm {$product->name} chỉ còn {$product->stock_quantity} sản phẩm.",
                    ]);
                }
            }

            $seatTotal = (float) BookingSeat::where('booking_id', $lockedBooking->id)
                ->sum('price');

            $concessionSubtotal = 0;

            foreach ($quantities as $productId => $quantity) {
                $concessionSubtotal +=
                    (float) $products->get($productId)->price * $quantity;
            }

            $lockedBooking->total_amount = $seatTotal + $concessionSubtotal;
            $lockedBooking->save();

            $existingOrder = ConcessionOrder::where('booking_id', $lockedBooking->id)
                ->lockForUpdate()
                ->first();

            if ($quantities->isEmpty()) {
                if ($existingOrder) {
                    $existingOrder->items()->delete();
                    $existingOrder->delete();
                }

                return;
            }

            $order = ConcessionOrder::updateOrCreate(
                ['booking_id' => $lockedBooking->id],
                [
                    'user_id' => $lockedBooking->user_id,
                    'order_code' => $existingOrder?->order_code
                        ?? 'YF-FD-' . strtoupper(\Illuminate\Support\Str::random(12)),
                    'subtotal' => $concessionSubtotal,
                    'discount_amount' => 0,
                    'total_amount' => $concessionSubtotal,
                    'status' => 'pending',
                    'payment_method' => null,
                    'payment_status' => 'pending',
                    'payment_reference' => null,
                    'paid_at' => null,
                ]
            );

            $order->items()->delete();

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                $order->items()->create([
                    'concession_product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => (float) $product->price * $quantity,
                ]);
            }
        });
    } catch (QueryException $exception) {
        throw $exception;
    }

    return redirect()
        ->route('bookings.payment', $booking)
        ->with('success', 'Đã lưu lựa chọn bắp nước. Bạn có thể thanh toán vé và bắp nước cùng lúc.');
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

        $mockPaymentEnabled = $this->mockPaymentEnabled();

        return view('bookings.payment', compact('booking', 'mockPaymentEnabled'));
    }

    
public function cancel(Booking $booking)
{
    abort_unless($booking->user_id === auth()->id(), 403);

    $result = DB::transaction(function () use ($booking) {
        $lockedBooking = Booking::whereKey($booking->id)
            ->lockForUpdate()
            ->firstOrFail();

        if (
            ! (
                $lockedBooking->status === 'pending'
                || (
                    $lockedBooking->status === 'confirmed'
                    && $lockedBooking->payment_status === 'paid'
                )
            )
        ) {
            return 'invalid';
        }

        $concessionOrder = ConcessionOrder::where(
            'booking_id',
            $lockedBooking->id
        )
            ->lockForUpdate()
            ->first();

        // Không hủy booking nếu bắp nước đã được chuẩn bị hoặc hoàn tất.
        if (
            $concessionOrder
            && in_array($concessionOrder->status, ['preparing', 'completed'], true)
        ) {
            return 'concession_processing';
        }

        // Booking chưa thanh toán.
        if ($lockedBooking->status === 'pending') {
            $lockedBooking->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'ticket_status' => 'cancelled',
            ]);

            if ($concessionOrder) {
                $concessionOrder->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                ]);
            }

            return 'cancelled';
        }

        // Booking đã thanh toán, bắp nước chưa được chuẩn bị.
        if ($concessionOrder && $concessionOrder->status === 'confirmed') {
            $concessionOrder->load('items');

            $items = $concessionOrder->items;

            // Gom số lượng theo sản phẩm.
            $quantities = $items
                ->filter(fn ($item) => $item->concession_product_id !== null)
                ->groupBy('concession_product_id')
                ->map(fn ($group) => (int) $group->sum('quantity'));

            // Khóa sản phẩm theo thứ tự ID để hạn chế deadlock.
            $products = ConcessionProduct::query()
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Hoàn lại kho.
            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if ($product && $quantity > 0) {
                    $product->increment('stock_quantity', $quantity);
                }
            }

            $concessionOrder->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
            ]);
        } elseif ($concessionOrder) {
            // Trường hợp đơn bắp nước không còn ở trạng thái confirmed.
            $concessionOrder->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
            ]);
        }

        $lockedBooking->update([
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'ticket_status' => 'cancelled',
        ]);

        return 'cancelled';
    });

    if ($result === 'invalid') {
        return redirect()
            ->route('bookings.show', $booking)
            ->withErrors([
                'booking' => 'Đơn đặt vé này không còn ở trạng thái có thể hủy.',
            ]);
    }

    if ($result === 'concession_processing') {
        return redirect()
            ->route('bookings.show', $booking)
            ->withErrors([
                'booking' => 'Bắp nước đã được chuẩn bị hoặc hoàn tất. Không thể tự hủy toàn bộ đơn hàng.',
            ]);
    }

    return redirect()
        ->route('bookings.show', $booking)
        ->with('success', 'Đã hủy đơn đặt vé và xử lý đơn bắp nước đi kèm.');
}

    
public function processPayment(Request $request, Booking $booking)
{
    abort_unless(
        $this->mockPaymentEnabled(),
        403,
        'Thanh toán mô phỏng không được bật trong môi trường này.'
    );

    abort_unless($booking->user_id === auth()->id(), 403);

    $request->validate([
        'payment_result' => ['prohibited'],
        'payment_status' => ['prohibited'],
        'payment_method' => [
            'sometimes',
            'required',
            Rule::in([self::MOCK_PAYMENT_METHOD]),
        ],
    ]);

    $processingResult = DB::transaction(function () use ($booking) {
        $lockedBooking = Booking::whereKey($booking->id)
            ->lockForUpdate()
            ->firstOrFail();

        // Không xử lý thanh toán lần thứ hai.
        if (
            $lockedBooking->status === 'confirmed'
            && $lockedBooking->payment_status === 'paid'
        ) {
            return [
                'redirect' => route('bookings.show', $lockedBooking),
                'with' => [
                    'success' => 'Booking này đã được thanh toán trước đó.',
                ],
            ];
        }

        if (
            $lockedBooking->status !== 'pending'
            || $lockedBooking->payment_status === 'paid'
        ) {
            return [
                'redirect' => route('bookings.show', $lockedBooking),
                'with' => [
                    'errors' => [
                        'payment' => 'Booking đã bị hủy hoặc giao dịch đã được xử lý.',
                    ],
                ],
            ];
        }

        // Kiểm tra thời gian giữ ghế.
        if (
            ! $lockedBooking->hold_expires_at
            || $lockedBooking->hold_expires_at->isPast()
        ) {
            $lockedBooking->update([
                'status' => 'cancelled',
                'payment_status' => 'timeout',
                'ticket_status' => 'cancelled',
            ]);

            ConcessionOrder::where('booking_id', $lockedBooking->id)
                ->where('payment_status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                ]);

            return [
                'redirect' => route('bookings.show', $lockedBooking),
                'with' => [
                    'errors' => [
                        'payment' => 'Thời gian giữ ghế đã hết. Vui lòng đặt lại ghế.',
                    ],
                ],
            ];
        }

        // Lấy đơn bắp nước gắn với booking.
        $concessionOrder = ConcessionOrder::where(
            'booking_id',
            $lockedBooking->id
        )
            ->lockForUpdate()
            ->first();

        $products = collect();

        if ($concessionOrder) {
            $concessionOrder->load('items');

            $items = $concessionOrder->items;

            // Kiểm tra dữ liệu sản phẩm.
            if ($items->contains(function ($item) {
                return ! $item->concession_product_id
                    || $item->quantity < 1;
            })) {
                return [
                    'redirect' => route('bookings.payment', $lockedBooking),
                    'with' => [
                        'errors' => [
                            'payment' => 'Có sản phẩm bắp nước không hợp lệ. Vui lòng kiểm tra lại đơn hàng.',
                        ],
                    ],
                ];
            }

            // Gom số lượng theo ID sản phẩm.
            $quantities = $items
                ->groupBy('concession_product_id')
                ->map(fn ($group) => (int) $group->sum('quantity'));

            // Khóa tất cả sản phẩm theo thứ tự ID.
            $products = ConcessionProduct::query()
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Kiểm tra toàn bộ tồn kho trước khi trừ bất kỳ sản phẩm nào.
            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if (
                    ! $product
                    || ! $product->is_active
                    || $product->stock_quantity < $quantity
                ) {
                    $productName = $items
                        ->firstWhere('concession_product_id', $productId)
                        ?->product_name ?? 'Sản phẩm bắp nước';

                    return [
                        'redirect' => route('bookings.payment', $lockedBooking),
                        'with' => [
                            'errors' => [
                                'payment' => 'Sản phẩm "' . $productName
                                    . '" không còn đủ số lượng. Vui lòng quay lại chọn sản phẩm khác.',
                            ],
                        ],
                    ];
                }
            }

            // Tất cả sản phẩm hợp lệ mới bắt đầu trừ tồn kho.
            foreach ($quantities as $productId => $quantity) {
                $products->get($productId)->decrement(
                    'stock_quantity',
                    $quantity
                );
            }
        }

        // Tạo mã giao dịch chung cho vé và bắp nước.
        $reference = $lockedBooking->payment_reference
            ?: 'YF-REF-' . strtoupper(
                \Illuminate\Support\Str::random(12)
            );

        // Xác nhận thanh toán booking.
        $lockedBooking->update([
            'payment_method' => self::MOCK_PAYMENT_METHOD,
            'payment_reference' => $reference,
            'payment_status' => 'paid',
            'paid_at' => now(),
            'status' => 'confirmed',
            'ticket_status' => 'available',
        ]);

        // Đồng bộ trạng thái đơn bắp nước.
        // Không tạo giao dịch thanh toán riêng.
        if ($concessionOrder) {
            $concessionOrder->update([
                'status' => 'confirmed',
                'payment_method' => self::MOCK_PAYMENT_METHOD,
                'payment_status' => 'paid',
                'payment_reference' => $reference,
                'paid_at' => now(),
            ]);
        }

        // Chỉ tính điểm thành viên dựa trên tiền vé.
        $ticketTotal = (float) BookingSeat::where(
            'booking_id',
            $lockedBooking->id
        )->sum('price');

        $user = $lockedBooking->user()->first();

        if ($user) {
            $pointsEarned = max(
                10,
                (int) round($ticketTotal / 10000)
            );

            $user->loyalty_points = ($user->loyalty_points ?? 0)
                + $pointsEarned;

            $user->member_tier = \App\Models\User::resolveMemberTier(
                $user->loyalty_points
            );

            $user->save();
        }

        return [
            'redirect' => route('bookings.show', $lockedBooking),
            'with' => [
                'success' => 'Thanh toán thành công. Vé và bắp nước đã được xác nhận.',
            ],
        ];
    });

    // Xử lý kết quả transaction.
    if (isset($processingResult['with']['errors'])) {
        return redirect($processingResult['redirect'])
            ->withErrors($processingResult['with']['errors']);
    }

    return redirect($processingResult['redirect'])
        ->with($processingResult['with']);
}


    private function mockPaymentEnabled(): bool
    {
        return app()->environment(['local', 'testing']);
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
    'concessionOrder.items',
]);

        return view('bookings.show', compact('booking'));
    }

   
private function expirePendingHolds(): void
{
    DB::transaction(function () {
        // Lấy các booking đã hết thời gian giữ ghế.
        $expiredBookings = Booking::query()
            ->where('status', 'pending')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);

        if ($expiredBookings->isEmpty()) {
            return;
        }

        $bookingIds = $expiredBookings->pluck('id');

        // Hủy booking và giải phóng trạng thái ghế.
        Booking::whereIn('id', $bookingIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'payment_status' => 'timeout',
                'ticket_status' => 'cancelled',
            ]);

        // Hủy các đơn bắp nước chưa thanh toán đi kèm.
        ConcessionOrder::whereIn('booking_id', $bookingIds)
            ->where('payment_status', 'pending')
            ->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
    });
}


    private function occupiedSeatIds(int $showtimeId): array
    {
        return BookingSeat::where('showtime_id', $showtimeId)
            ->whereHas('booking', fn ($query) => $query
                ->where('status', 'confirmed')
                ->orWhere(function ($query) {
                    $query->where('status', 'pending')
                        ->whereNotNull('hold_expires_at')
                        ->where('hold_expires_at', '>', now());
                }))
            ->pluck('seat_id')
            ->map(fn ($seatId) => (int) $seatId)
            ->all();
    }
}
