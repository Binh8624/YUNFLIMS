<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cinema;
use App\Models\Format;
use App\Models\LoyaltyPointTransaction;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use App\Services\Payment\MockGateway;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function makeBooking(): Booking
    {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => true, 'loyalty_points' => 0])->save();
        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = Cinema::create([
            'name' => 'Pay cinema ' . Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'P' . Str::upper(Str::random(10)),
            'name' => 'Pay room',
            'total_seats' => 4,
            'is_active' => true,
        ]);
        $room->formats()->attach($format);
        $movie = Movie::create([
            'title' => 'Pay movie ' . Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
        ]);
        $movie->formats()->attach($format);
        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $format->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addMinutes(90),
            'price' => 80000,
            'status' => 'scheduled',
        ]);
        $seats = collect(range(1, 4))->map(fn (int $number) => Seat::create([
            'room_id' => $room->id,
            'seat_code' => 'A' . $number,
            'row_number' => 1,
            'seat_number' => $number,
            'seat_type' => 'standard',
            'is_active' => true,
        ]));

        $booking = Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'booking_code' => Booking::generateBookingCode(),
            'total_amount' => 160000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'hold_expires_at' => now()->addMinutes(5),
            'qr_code' => Booking::generateQrSvg('test-' . Str::random(6)),
            'ticket_status' => 'available',
        ]);
        foreach ($seats->take(2) as $seat) {
            BookingSeat::create([
                'booking_id' => $booking->id,
                'showtime_id' => $showtime->id,
                'seat_id' => $seat->id,
                'price' => 80000,
            ]);
        }

        return $booking;
    }

    private function initiate(Booking $booking): Payment
    {
        return app(PaymentService::class)->initiate($booking, 'mock', 'mock');
    }

    private function callbackPayload(Payment $payment, string $status, array $overrides = []): array
    {
        $payload = array_merge([
            'order_ref' => $payment->order_ref,
            'transaction_code' => 'MOCK-' . strtoupper(Str::random(10)),
            'amount' => (string) $payment->amount,
            'status' => $status,
            'nonce' => Str::random(8),
        ], $overrides);
        $payload['signature'] = (new MockGateway)->sign($payload);

        return $payload;
    }

    public function test_success_webhook_confirms_booking_once_and_awards_points_once(): void
    {
        $booking = $this->makeBooking();
        $payment = $this->initiate($booking);
        $payload = $this->callbackPayload($payment, 'success');

        $this->postJson(route('payment.webhook', ['gateway' => 'mock']), $payload)->assertOk();

        $payment->refresh();
        $booking->refresh();
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->transaction_code);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame(16, $booking->points_earned);
        $this->assertSame(16, $booking->user->loyalty_points);
        $this->assertSame(1, LoyaltyPointTransaction::where('booking_id', $booking->id)->count());

        $this->postJson(route('payment.webhook', ['gateway' => 'mock']), $payload)->assertOk();
        $this->postJson(route('payment.webhook', ['gateway' => 'mock']), $this->callbackPayload($payment, 'success'))->assertOk();

        $booking->refresh();
        $this->assertSame(16, $booking->user->loyalty_points);
        $this->assertSame(1, LoyaltyPointTransaction::where('booking_id', $booking->id)->count());
        $this->assertSame(1, Payment::where('booking_id', $booking->id)->count());
        $this->assertSame(2, PaymentEvent::where('payment_id', $payment->id)->count());
    }

    public function test_invalid_signature_never_marks_paid(): void
    {
        $booking = $this->makeBooking();
        $payment = $this->initiate($booking);
        $payload = $this->callbackPayload($payment, 'success');
        $payload['signature'] = str_repeat('0', 64);

        $this->postJson(route('payment.webhook', ['gateway' => 'mock']), $payload)->assertOk();

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertSame('pending', $booking->refresh()->payment_status);
        $this->assertFalse(PaymentEvent::where('payment_id', $payment->id)->first()->signature_valid);
    }

    public function test_amount_mismatch_never_marks_paid(): void
    {
        $booking = $this->makeBooking();
        $payment = $this->initiate($booking);
        $payload = $this->callbackPayload($payment, 'success', ['amount' => '1000']);

        $this->postJson(route('payment.webhook', ['gateway' => 'mock']), $payload)->assertOk();

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertSame('pending', $booking->refresh()->payment_status);
    }

    public function test_failed_webhook_cancels_booking_and_releases_seats(): void
    {
        $booking = $this->makeBooking();
        $payment = $this->initiate($booking);

        $this->postJson(route('payment.webhook', ['gateway' => 'mock']), $this->callbackPayload($payment, 'failed'))->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, $payment->refresh()->status);
        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('failed', $booking->payment_status);
        $this->assertSame(0, BookingSeat::where('booking_id', $booking->id)->whereNull('released_at')->count());
    }

    public function test_client_cannot_submit_payment_result_directly(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($booking->user)
            ->post(route('bookings.payment.process', $booking), ['payment_result' => 'success'])
            ->assertSessionHasErrors('gateway');

        $this->assertSame('pending', $booking->refresh()->payment_status);
        $this->assertSame(0, Payment::where('booking_id', $booking->id)->count());
    }

    public function test_initiate_reuses_pending_payment_for_same_booking(): void
    {
        $booking = $this->makeBooking();
        $first = $this->initiate($booking);
        $second = $this->initiate($booking);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Payment::where('booking_id', $booking->id)->count());
    }
}