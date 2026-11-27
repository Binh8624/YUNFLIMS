<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MomoGateway implements PaymentGateway
{
    private const PENDING_RESULT_CODES = ['1000', '9000'];

    public function name(): string
    {
        return 'momo';
    }

    public function createPaymentUrl(Payment $payment): string
    {
        $payment->loadMissing('booking');
        $requestPayload = $payment->request_payload ?? [];
        $savedPayUrl = $requestPayload['momo_pay_url'] ?? null;
        if (is_string($savedPayUrl) && $this->isMomoUrl($savedPayUrl)) {
            return $savedPayUrl;
        }

        $requestId = (string) Str::uuid();
        $params = [
            'partnerCode' => $this->config('partner_code'),
            'partnerName' => (string) config('payment.gateways.momo.partner_name', 'YUNFLIMS'),
            'storeId' => (string) config('payment.gateways.momo.store_id', 'YUNFLIMS'),
            'requestId' => $requestId,
            'amount' => (string) (int) round((float) $payment->amount),
            'orderId' => $payment->order_ref,
            'orderInfo' => 'Thanh toan ve YUNFLIMS ' . $payment->order_ref,
            'redirectUrl' => route('payment.return', ['gateway' => 'momo']),
            'ipnUrl' => route('payment.webhook', ['gateway' => 'momo']),
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => 'captureWallet',
        ];
        $params['signature'] = $this->sign($this->createSignaturePayload($params));

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(15)
            ->post($this->config('create_url'), $params);
        $result = $response->json();
        $payUrl = is_array($result) ? ($result['payUrl'] ?? null) : null;

        if (
            ! $response->successful()
            || ! is_array($result)
            || (string) ($result['resultCode'] ?? '') !== '0'
            || ! is_string($payUrl)
            || ! $this->isMomoUrl($payUrl)
        ) {
            $resultCode = is_array($result) ? (string) ($result['resultCode'] ?? 'unknown') : 'unknown';
            throw new RuntimeException('MoMo không thể khởi tạo giao dịch (HTTP ' . $response->status() . ', mã ' . $resultCode . ').');
        }

        $payment->update([
            'request_payload' => array_merge($requestPayload, [
                'momo_request_id' => $requestId,
                'momo_pay_url' => $payUrl,
            ]),
        ]);

        return $payUrl;
    }

    public function verifyCallback(array $payload): array
    {
        $required = [
            'amount',
            'extraData',
            'message',
            'orderId',
            'orderInfo',
            'orderType',
            'partnerCode',
            'payType',
            'requestId',
            'responseTime',
            'resultCode',
            'transId',
        ];
        $hasRequiredFields = collect($required)->every(
            fn (string $field) => array_key_exists($field, $payload)
        );
        $expectedSignature = $hasRequiredFields
            ? $this->sign($this->callbackSignaturePayload($payload))
            : '';
        $signature = (string) ($payload['signature'] ?? '');
        $signatureValid = $signature !== ''
            && $expectedSignature !== ''
            && hash_equals($expectedSignature, $signature);
        $resultCode = (string) ($payload['resultCode'] ?? '');

        return [
            'signature_valid' => $signatureValid,
            'status' => match (true) {
                $resultCode === '0' => Payment::STATUS_SUCCEEDED,
                in_array($resultCode, self::PENDING_RESULT_CODES, true) => Payment::STATUS_PENDING,
                $resultCode !== '' => Payment::STATUS_FAILED,
                default => Payment::STATUS_PENDING,
            },
            'order_ref' => isset($payload['orderId']) ? (string) $payload['orderId'] : null,
            'transaction_code' => isset($payload['transId']) ? (string) $payload['transId'] : null,
            'amount' => isset($payload['amount']) ? (float) $payload['amount'] : null,
            'message' => isset($payload['message']) ? (string) $payload['message'] : null,
        ];
    }

    public function queryTransaction(Payment $payment): array
    {
        $requestId = (string) Str::uuid();
        $params = [
            'partnerCode' => $this->config('partner_code'),
            'requestId' => $requestId,
            'orderId' => $payment->order_ref,
            'lang' => 'vi',
        ];
        $params['signature'] = $this->sign(implode('&', [
            'accessKey=' . $this->config('access_key'),
            'orderId=' . $params['orderId'],
            'partnerCode=' . $params['partnerCode'],
            'requestId=' . $params['requestId'],
        ]));

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(15)
            ->post($this->config('query_url'), $params);
        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            return [
                'signature_valid' => false,
                'status' => Payment::STATUS_PENDING,
                'order_ref' => $payment->order_ref,
                'transaction_code' => null,
                'amount' => null,
                'message' => 'MoMo chưa trả được trạng thái giao dịch.',
                'raw' => is_array($payload) ? $payload : [],
            ];
        }

        $result = $this->verifyCallback($payload);
        if (! $result['signature_valid']) {
            $result['status'] = Payment::STATUS_PENDING;
            $result['message'] = 'Chữ ký phản hồi truy vấn MoMo không hợp lệ.';
        }

        return $result + ['raw' => $payload];
    }

    public function refund(Payment $payment, PaymentRefund $refund): array
    {
        return [
            'signature_valid' => false,
            'status' => PaymentRefund::STATUS_FAILED,
            'gateway_refund_id' => null,
            'message' => 'Hoàn tiền MoMo chưa được bật; hãy xử lý yêu cầu trong cổng merchant MoMo.',
            'raw' => [],
        ];
    }

    private function createSignaturePayload(array $params): string
    {
        return implode('&', [
            'accessKey=' . $this->config('access_key'),
            'amount=' . $params['amount'],
            'extraData=' . $params['extraData'],
            'ipnUrl=' . $params['ipnUrl'],
            'orderId=' . $params['orderId'],
            'orderInfo=' . $params['orderInfo'],
            'partnerCode=' . $params['partnerCode'],
            'redirectUrl=' . $params['redirectUrl'],
            'requestId=' . $params['requestId'],
            'requestType=' . $params['requestType'],
        ]);
    }

    private function callbackSignaturePayload(array $payload): string
    {
        return implode('&', [
            'accessKey=' . $this->config('access_key'),
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
    }

    private function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->config('secret_key'));
    }

    private function isMomoUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        return ($parts['scheme'] ?? null) === 'https'
            && ($host === 'momo.vn' || str_ends_with($host, '.momo.vn'));
    }

    private function config(string $key): string
    {
        $value = (string) config('payment.gateways.momo.' . $key);
        if ($value === '') {
            throw new RuntimeException('MoMo chưa được cấu hình ' . $key . '.');
        }

        return $value;
    }
}
