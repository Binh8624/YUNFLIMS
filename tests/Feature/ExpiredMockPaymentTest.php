<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTestBooking;
use Tests\TestCase;

class ExpiredMockPaymentTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTestBooking;

    public function test_getting_mock_simulation_url_does_not_confirm_a_pending_payment(): void
    {
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'mock', 'mock');

        $this->actingAs($booking->user)
            ->get(route('payment.mock.simulate.result', $payment))
            ->assertRedirect(route('payment.mock.checkout', $payment));

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertSame('pending', $booking->refresh()->status);
    }

    public function test_getting_mock_simulation_url_after_success_returns_to_the_booking(): void
    {
        Mail::fake();
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'mock', 'mock');

        $this->actingAs($booking->user)
            ->post(route('payment.mock.simulate', $payment), ['result' => 'success'])
            ->assertRedirect();

        $this->get(route('payment.mock.simulate.result', $payment))
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('success');

        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->refresh()->status);
        $this->assertSame('confirmed', $booking->refresh()->status);
    }

    public function test_mock_gateway_cannot_confirm_payment_after_the_seat_hold_expires(): void
    {
        Mail::fake();
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'mock', 'mock');
        $booking->update(['hold_expires_at' => now()->subMinute()]);

        $this->actingAs($booking->user)
            ->post(route('payment.mock.simulate', $payment), ['result' => 'success'])
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHasErrors('payment');

        $this->assertSame(Payment::STATUS_TIMEOUT, $payment->refresh()->status);
        $this->assertSame('cancelled', $booking->refresh()->status);
        $this->assertSame('timeout', $booking->payment_status);
    }

    public function test_expired_mock_payment_does_not_block_rebooking_the_released_seats(): void
    {
        Mail::fake();
        $oldBooking = $this->makeBooking();
        app(PaymentService::class)->initiate($oldBooking, 'mock', 'mock');
        $oldBooking->update(['hold_expires_at' => now()->subMinute()]);
        $seatIds = BookingSeat::where('booking_id', $oldBooking->id)->pluck('seat_id')->all();

        $this->assertSame(1, Booking::releaseExpiredHolds());
        $this->assertSame('cancelled', $oldBooking->fresh()->status);
        $this->assertSame(
            0,
            BookingSeat::where('booking_id', $oldBooking->id)->whereNull('released_at')->count()
        );

        $customer = User::factory()->create(['is_active' => true]);
        $response = $this->actingAs($customer)
            ->post(route('bookings.store', $oldBooking->showtime_id), ['seats' => $seatIds]);
        $newBooking = Booking::where('user_id', $customer->id)->firstOrFail();

        $response->assertRedirect(route('bookings.concessions', $newBooking));
        $this->assertSame(
            count($seatIds),
            BookingSeat::where('booking_id', $newBooking->id)->whereNull('released_at')->count()
        );
    }
}
