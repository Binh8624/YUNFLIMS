<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\LoyaltyPointTransaction;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use App\Services\Payment\MockGateway;
use App\Services\Payment\PaymentService;
use App\Services\Payment\RefundService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTestBooking;
use Tests\TestCase;

class RefundFlowTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTestBooking;

    private function pay(Booking $booking): Payment
    {
        $payment = app(PaymentService::class)->initiate($booking, 'mock', 'mock');
        $payload = [
            'order_ref' => $payment->order_ref,
            'transaction_code' => 'MOCK-' . strtoupper(Str::random(10)),
            'amount' => (string) $payment->amount,
            'status' => 'success',
            'nonce' => Str::random(8),
        ];
        $payload['signature'] = (new MockGateway)->sign($payload);
        app(PaymentService::class)->handleCallback('mock', $payload, 'webhook');

        return $payment->refresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_active' => true, 'role' => User::ROLE_SUPER_ADMIN])->save();

        return $admin;
    }

    public function test_customer_cancel_paid_refunds_and_revokes_points(): void
    {
        $booking = $this->makeBooking();
        $this->pay($booking);
        $booking->refresh();
        $this->assertSame(16, $booking->user->loyalty_points);

        $this->actingAs($booking->user)
            ->post(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.show', $booking));

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('refunded', $booking->payment_status);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertSame($booking->user_id, $booking->cancelled_by);
        $this->assertSame(0, $booking->user->loyalty_points);
        $this->assertSame(0, BookingSeat::where('booking_id', $booking->id)->whereNull('released_at')->count());

        $refund = PaymentRefund::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame(PaymentRefund::STATUS_SUCCEEDED, $refund->status);
        $this->assertNotNull($refund->gateway_refund_id);
        $this->assertNotNull($refund->processed_at);
        $this->assertSame(Payment::STATUS_REFUNDED, $refund->payment->status);
        $this->assertNotNull($refund->payment->refunded_at);
        $this->assertSame(1, LoyaltyPointTransaction::where('booking_id', $booking->id)->where('type', 'revoke')->count());
    }

    public function test_second_cancel_is_rejected_and_points_revoked_once(): void
    {
        $booking = $this->makeBooking();
        $this->pay($booking);

        $this->actingAs($booking->user)->post(route('bookings.cancel', $booking));
        $this->actingAs($booking->user)
            ->post(route('bookings.cancel', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertSame(1, PaymentRefund::where('booking_id', $booking->id)->count());
        $this->assertSame(1, LoyaltyPointTransaction::where('booking_id', $booking->id)->where('type', 'revoke')->count());
        $this->assertSame(0, $booking->user->refresh()->loyalty_points);
    }

    public function test_cancel_after_showtime_is_rejected(): void
    {
        $booking = $this->makeBooking();
        $this->pay($booking);
        $booking->showtime->update(['starts_at' => now()->subMinute()]);

        $this->actingAs($booking->user)
            ->post(route('bookings.cancel', $booking))
            ->assertSessionHasErrors('booking');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame(0, PaymentRefund::where('booking_id', $booking->id)->count());
    }

    public function test_checked_in_ticket_cannot_be_refunded(): void
    {
        $booking = $this->makeBooking();
        $this->pay($booking);
        $booking->update(['ticket_used_at' => now(), 'checkin_count' => 1]);

        $this->actingAs($booking->user)
            ->post(route('bookings.cancel', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertSame('paid', $booking->refresh()->payment_status);
        $this->assertSame(16, $booking->user->loyalty_points);
    }

    public function test_pending_cancel_releases_seats_without_refund(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($booking->user)->post(route('bookings.cancel', $booking));

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame(0, BookingSeat::where('booking_id', $booking->id)->whereNull('released_at')->count());
        $this->assertSame(0, PaymentRefund::where('booking_id', $booking->id)->count());
    }

    public function test_admin_cancel_paid_requires_reason_and_audits(): void
    {
        $booking = $this->makeBooking();
        $this->pay($booking);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.bookings.cancel', $booking))
            ->assertSessionHasErrors('reason');
        $this->assertSame('paid', $booking->refresh()->payment_status);

        $this->actingAs($admin)
            ->patch(route('admin.bookings.cancel', $booking), ['reason' => 'Khách khiếu nại, duyệt hoàn'])
            ->assertRedirect(route('admin.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame('refunded', $booking->payment_status);
        $this->assertSame($admin->id, $booking->cancelled_by);
        $this->assertSame('Khách khiếu nại, duyệt hoàn', $booking->cancel_reason);
        $refund = PaymentRefund::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame($admin->id, $refund->requested_by);
    }

    public function test_gateway_refund_failure_keeps_ticket_paid(): void
    {
        config([
            'payment.gateways.vnpay.enabled' => true,
            'payment.gateways.vnpay.tmn_code' => 'TESTTMN',
            'payment.gateways.vnpay.hash_secret' => 'testsecret',
        ]);
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'vnpay', 'vnpay_qr');
        $payload = [
            'vnp_TxnRef' => $payment->order_ref,
            'vnp_Amount' => (int) round((float) $payment->amount * 100),
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => 'VNP-TEST',
        ];
        ksort($payload);
        $payload['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($payload, '', '&', PHP_QUERY_RFC1738), 'testsecret');
        app(PaymentService::class)->handleCallback('vnpay', $payload, 'webhook');
        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);

        $response = ['vnp_TxnRef' => $payment->order_ref, 'vnp_ResponseCode' => '02'];
        ksort($response);
        $response['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($response, '', '&', PHP_QUERY_RFC1738), 'testsecret');
        Http::fake(['*' => Http::response($response)]);

        $refund = app(RefundService::class)->request($booking->refresh(), $booking->user, 'test');

        $this->assertSame(PaymentRefund::STATUS_FAILED, $refund->status);
        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame(16, $booking->user->loyalty_points);
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->refresh()->status);
    }
}