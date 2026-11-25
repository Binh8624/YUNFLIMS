<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTestBooking;
use Tests\TestCase;

class ReconcilePaymentsTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;
    use CreatesTestBooking;

    public function test_command_expires_hold_and_releases_seats_and_payment(): void
    {
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'mock', 'mock');
        $booking->update(['hold_expires_at' => now()->subMinute()]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('timeout', $booking->payment_status);
        $this->assertSame(Payment::STATUS_TIMEOUT, $payment->refresh()->status);
        $this->assertSame(0, BookingSeat::where('booking_id', $booking->id)->whereNull('released_at')->count());
    }

    public function test_reconcile_keeps_pending_when_gateway_reports_pending(): void
    {
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'mock', 'mock');

        app(PaymentService::class)->reconcile($payment);

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertSame('pending', $booking->refresh()->payment_status);
    }

    public function test_vnpay_querydr_confirms_payment_when_webhook_was_missed(): void
    {
        config([
            'payment.gateways.vnpay.enabled' => true,
            'payment.gateways.vnpay.tmn_code' => 'TESTTMN',
            'payment.gateways.vnpay.hash_secret' => 'testsecret',
        ]);
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'vnpay', 'vnpay_qr');
        $payment->forceFill(['created_at' => now()->subMinutes(3)])->save();

        $response = [
            'vnp_TxnRef' => $payment->order_ref,
            'vnp_Amount' => (int) round((float) $payment->amount * 100),
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => '12345678',
        ];
        ksort($response);
        $response['vnp_SecureHash'] = hash_hmac(
            'sha512',
            http_build_query($response, '', '&', PHP_QUERY_RFC1738),
            'testsecret'
        );
        Http::fake(['*' => Http::response($response)]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->status);
        $this->assertSame('12345678', $payment->transaction_code);
        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
    }

    public function test_vnpay_querydr_with_bad_signature_does_not_confirm(): void
    {
        config([
            'payment.gateways.vnpay.enabled' => true,
            'payment.gateways.vnpay.tmn_code' => 'TESTTMN',
            'payment.gateways.vnpay.hash_secret' => 'testsecret',
        ]);
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'vnpay', 'vnpay_qr');
        $payment->forceFill(['created_at' => now()->subMinutes(3)])->save();

        Http::fake(['*' => Http::response([
            'vnp_TxnRef' => $payment->order_ref,
            'vnp_Amount' => (int) round((float) $payment->amount * 100),
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_SecureHash' => str_repeat('0', 128),
        ])]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertSame('pending', $booking->refresh()->payment_status);
    }
}