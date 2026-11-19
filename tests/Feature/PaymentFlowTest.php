<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\LoyaltyPointTransaction;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payment\MockGateway;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTestBooking;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTestBooking;

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