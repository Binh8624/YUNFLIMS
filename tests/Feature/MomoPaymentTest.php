<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTestBooking;
use Tests\TestCase;

class MomoPaymentTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTestBooking;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.gateways.mock.enabled' => false,
            'payment.gateways.momo.enabled' => true,
            'payment.gateways.momo.partner_code' => 'MOMO_TEST',
            'payment.gateways.momo.access_key' => 'test-access-key',
            'payment.gateways.momo.secret_key' => 'test-secret-key',
            'payment.gateways.momo.create_url' => 'https://test-payment.momo.vn/v2/gateway/api/create',
            'payment.gateways.momo.query_url' => 'https://test-payment.momo.vn/v2/gateway/api/query',
        ]);
    }

    public function test_momo_gateway_is_not_listed_without_merchant_credentials(): void
    {
        config([
            'payment.gateways.momo.partner_code' => '',
            'payment.gateways.momo.access_key' => '',
            'payment.gateways.momo.secret_key' => '',
        ]);

        $this->assertSame([], app(PaymentService::class)->enabledGateways());
    }

    public function test_payment_page_does_not_offer_mock_when_real_gateways_are_unconfigured(): void
    {
        config([
            'payment.gateways.vnpay.enabled' => false,
            'payment.gateways.momo.partner_code' => '',
            'payment.gateways.momo.access_key' => '',
            'payment.gateways.momo.secret_key' => '',
        ]);
        $booking = $this->makeBooking();

        $this->actingAs($booking->user)
            ->get(route('bookings.payment', $booking))
            ->assertOk()
            ->assertSee('Cổng thanh toán thật chưa được cấu hình')
            ->assertDontSee('Cổng mô phỏng');
    }

    public function test_payment_page_shows_vnpay_and_momo_when_both_are_configured(): void
    {
        config([
            'payment.gateways.vnpay.enabled' => true,
            'payment.gateways.vnpay.tmn_code' => 'VNPAY_TEST',
            'payment.gateways.vnpay.hash_secret' => 'test-vnpay-secret',
        ]);
        $booking = $this->makeBooking();

        $this->actingAs($booking->user)
            ->get(route('bookings.payment', $booking))
            ->assertOk()
            ->assertSee('VNPAY')
            ->assertSee('MOMO')
            ->assertSee('QR VNPay')
            ->assertSee('Ví MoMo');
    }

    public function test_payment_creation_signs_request_and_redirects_to_momo_pay_url(): void
    {
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'momo', 'momo_wallet');
        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => Http::response([
                'resultCode' => 0,
                'payUrl' => 'https://test-payment.momo.vn/gw_payment/transactionProcessor',
            ]),
        ]);

        $url = app(PaymentService::class)->gateway('momo')->createPaymentUrl($payment);

        $this->assertSame('https://test-payment.momo.vn/gw_payment/transactionProcessor', $url);
        Http::assertSent(function (ClientRequest $request) use ($payment) {
            $params = $request->data();
            $signature = $params['signature'];
            unset($params['signature']);
            $rawHash = implode('&', [
                'accessKey=test-access-key',
                'amount=' . (int) round((float) $payment->amount),
                'extraData=',
                'ipnUrl=' . route('payment.webhook', ['gateway' => 'momo']),
                'orderId=' . $payment->order_ref,
                'orderInfo=Thanh toan ve YUNFLIMS ' . $payment->order_ref,
                'partnerCode=MOMO_TEST',
                'redirectUrl=' . route('payment.return', ['gateway' => 'momo']),
                'requestId=' . $params['requestId'],
                'requestType=captureWallet',
            ]);

            return $request->url() === 'https://test-payment.momo.vn/v2/gateway/api/create'
                && $params['orderId'] === $payment->order_ref
                && hash_equals(hash_hmac('sha256', $rawHash, 'test-secret-key'), $signature);
        });
    }

    public function test_payment_retry_reuses_the_existing_momo_checkout_url(): void
    {
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'momo', 'momo_wallet');
        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => Http::response([
                'resultCode' => 0,
                'payUrl' => 'https://test-payment.momo.vn/gw_payment/transactionProcessor',
            ]),
        ]);
        $gateway = app(PaymentService::class)->gateway('momo');

        $firstUrl = $gateway->createPaymentUrl($payment);
        $retryUrl = $gateway->createPaymentUrl($payment->fresh());

        $this->assertSame($firstUrl, $retryUrl);
        Http::assertSentCount(1);
    }

    public function test_valid_momo_webhook_confirms_booking_and_returns_acknowledgement(): void
    {
        Mail::fake();
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'momo', 'momo_wallet');
        $payload = $this->callbackPayload($payment);

        $response = $this->postJson(route('payment.webhook', ['gateway' => 'momo']), $payload)
            ->assertOk()
            ->assertJsonPath('resultCode', 0);

        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->refresh()->status);
        $this->assertSame('confirmed', $booking->refresh()->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('Successful', $response->json('message'));
    }

    public function test_momo_webhook_with_bad_signature_does_not_confirm_booking(): void
    {
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'momo', 'momo_wallet');
        $payload = $this->callbackPayload($payment);
        $payload['signature'] = str_repeat('0', 64);

        $this->postJson(route('payment.webhook', ['gateway' => 'momo']), $payload)
            ->assertOk()
            ->assertJsonPath('resultCode', 1);

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertSame('pending', $booking->refresh()->payment_status);
    }

    public function test_momo_webhook_with_signed_amount_mismatch_does_not_confirm_booking(): void
    {
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'momo', 'momo_wallet');
        $payload = $this->callbackPayload($payment, ['amount' => '1']);

        $this->postJson(route('payment.webhook', ['gateway' => 'momo']), $payload)
            ->assertOk();

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertSame('pending', $booking->refresh()->payment_status);
    }

    public function test_momo_query_can_reconcile_a_verified_successful_payment(): void
    {
        Mail::fake();
        $booking = $this->makeBooking();
        $payment = app(PaymentService::class)->initiate($booking, 'momo', 'momo_wallet');
        $payment->forceFill(['created_at' => now()->subMinutes(3)])->save();
        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/query' => Http::response(
                $this->callbackPayload($payment)
            ),
        ]);

        app(PaymentService::class)->reconcile($payment);

        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->refresh()->status);
        $this->assertSame('confirmed', $booking->refresh()->status);
    }

    private function callbackPayload(Payment $payment, array $overrides = []): array
    {
        $payload = array_merge([
            'amount' => (string) (int) round((float) $payment->amount),
            'extraData' => '',
            'message' => 'Successful',
            'orderId' => $payment->order_ref,
            'orderInfo' => 'Thanh toan ve YUNFLIMS ' . $payment->order_ref,
            'orderType' => 'momo_wallet',
            'partnerCode' => 'MOMO_TEST',
            'payType' => 'qr',
            'requestId' => 'request-' . $payment->id,
            'responseTime' => '20261126090000',
            'resultCode' => '0',
            'transId' => 'MOMO-' . $payment->id,
        ], $overrides);
        $rawHash = implode('&', [
            'accessKey=test-access-key',
            'amount=' . $payload['amount'],
            'extraData=' . $payload['extraData'],
            'message=' . $payload['message'],
            'orderId=' . $payload['orderId'],
            'orderInfo=' . $payload['orderInfo'],
            'orderType=' . $payload['orderType'],
            'partnerCode=' . $payload['partnerCode'],
            'payType=' . $payload['payType'],
            'requestId=' . $payload['requestId'],
            'responseTime=' . $payload['responseTime'],
            'resultCode=' . $payload['resultCode'],
            'transId=' . $payload['transId'],
        ]);
        $payload['signature'] = hash_hmac('sha256', $rawHash, 'test-secret-key');

        return $payload;
    }
}
