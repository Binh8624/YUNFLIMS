<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VnpayGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'vnpay';
    }

    public function createPaymentUrl(Payment $payment): string
    {
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->config('tmn_code'),
            'vnp_Amount' => (int) round((float) $payment->amount * 100),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $payment->order_ref,
            'vnp_OrderInfo' => 'Thanh toan ve YUNFLIMS ' . $payment->order_ref,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => route('payment.return', ['gateway' => 'vnpay']),
            'vnp_IpAddr' => request()->ip(),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_ExpireDate' => $payment->booking->hold_expires_at?->format('YmdHis') ?? now()->addMinutes(15)->format('YmdHis'),
        ];

        if ($payment->method === 'vnpay_qr') {
            $params['vnp_BankCode'] = 'VNPAYQR';
        }

        return $this->config('pay_url') . '?' . $this->buildQuery($params) . '&vnp_SecureHash=' . $this->hash($params);
    }

    public function verifyCallback(array $payload): array
    {
        $secureHash = (string) ($payload['vnp_SecureHash'] ?? '');
        unset($payload['vnp_SecureHash'], $payload['vnp_SecureHashType']);

        $signatureValid = $secureHash !== '' && hash_equals($this->hash($payload), $secureHash);
        $responseCode = (string) ($payload['vnp_ResponseCode'] ?? '');
        $transactionStatus = $payload['vnp_TransactionStatus'] ?? null;

        $status = Payment::STATUS_FAILED;
        if ($responseCode === '00') {
            $status = match ((string) $transactionStatus) {
                '', '00' => Payment::STATUS_SUCCEEDED,
                '01' => Payment::STATUS_PENDING,
                default => Payment::STATUS_FAILED,
            };
        }

        return [
            'signature_valid' => $signatureValid,
            'status' => $status,
            'order_ref' => isset($payload['vnp_TxnRef']) ? (string) $payload['vnp_TxnRef'] : null,
            'transaction_code' => isset($payload['vnp_TransactionNo']) ? (string) $payload['vnp_TransactionNo'] : null,
            'amount' => isset($payload['vnp_Amount']) ? ((float) $payload['vnp_Amount']) / 100 : null,
            'message' => 'VNPay response code ' . $responseCode,
        ];
    }

    public function queryTransaction(Payment $payment): array
    {
        $params = [
            'vnp_RequestId' => (string) random_int(10000000, 99999999),
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'querydr',
            'vnp_TmnCode' => $this->config('tmn_code'),
            'vnp_TxnRef' => $payment->order_ref,
            'vnp_OrderInfo' => 'Query dr ' . $payment->order_ref,
            'vnp_TransactionDate' => $payment->created_at->format('YmdHis'),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_IpAddr' => request()->ip() ?? '127.0.0.1',
        ];
        $params['vnp_SecureHash'] = $this->hash($params);

        $response = Http::asForm()->timeout(15)->post($this->config('api_url'), $params)->json();
        if (! is_array($response)) {
            return [
                'signature_valid' => false,
                'status' => Payment::STATUS_PENDING,
                'order_ref' => $payment->order_ref,
                'transaction_code' => null,
                'amount' => null,
                'message' => 'VNPay querydr không phản hồi',
                'raw' => [],
            ];
        }

        $secureHash = (string) ($response['vnp_SecureHash'] ?? '');
        $unsigned = $response;
        unset($unsigned['vnp_SecureHash'], $unsigned['vnp_SecureHashType']);
        $signatureValid = $secureHash !== '' && hash_equals($this->hash($unsigned), $secureHash);

        $status = Payment::STATUS_PENDING;
        if (($response['vnp_ResponseCode'] ?? '') === '00') {
            $status = match ((string) ($response['vnp_TransactionStatus'] ?? '')) {
                '00' => Payment::STATUS_SUCCEEDED,
                '01' => Payment::STATUS_PENDING,
                default => Payment::STATUS_FAILED,
            };
        }

        return [
            'signature_valid' => $signatureValid,
            'status' => $status,
            'order_ref' => isset($response['vnp_TxnRef']) ? (string) $response['vnp_TxnRef'] : null,
            'transaction_code' => isset($response['vnp_TransactionNo']) ? (string) $response['vnp_TransactionNo'] : null,
            'amount' => isset($response['vnp_Amount']) ? ((float) $response['vnp_Amount']) / 100 : null,
            'message' => 'VNPay querydr response code ' . ($response['vnp_ResponseCode'] ?? ''),
            'raw' => $response,
        ];
    }

    public function refund(Payment $payment, PaymentRefund $refund): array
    {
        $params = [
            'vnp_RequestId' => (string) random_int(10000000, 99999999),
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'refund',
            'vnp_TmnCode' => $this->config('tmn_code'),
            'vnp_TransactionType' => '02',
            'vnp_TxnRef' => $payment->order_ref,
            'vnp_Amount' => (int) round((float) $refund->amount * 100),
            'vnp_OrderInfo' => 'Hoan tien ' . $payment->order_ref,
            'vnp_TransactionDate' => $payment->created_at->format('YmdHis'),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_IpAddr' => request()->ip() ?? '127.0.0.1',
        ];
        $params['vnp_SecureHash'] = $this->hash($params);

        $response = Http::asForm()->timeout(15)->post($this->config('api_url'), $params)->json();
        if (! is_array($response)) {
            return [
                'signature_valid' => false,
                'status' => PaymentRefund::STATUS_FAILED,
                'gateway_refund_id' => null,
                'message' => 'VNPay refund không phản hồi',
                'raw' => [],
            ];
        }

        $secureHash = (string) ($response['vnp_SecureHash'] ?? '');
        $unsigned = $response;
        unset($unsigned['vnp_SecureHash'], $unsigned['vnp_SecureHashType']);

        return [
            'signature_valid' => $secureHash !== '' && hash_equals($this->hash($unsigned), $secureHash),
            'status' => ($response['vnp_ResponseCode'] ?? '') === '00'
                ? PaymentRefund::STATUS_SUCCEEDED
                : PaymentRefund::STATUS_FAILED,
            'gateway_refund_id' => isset($response['vnp_TransactionNo']) ? (string) $response['vnp_TransactionNo'] : null,
            'message' => 'VNPay refund response code ' . ($response['vnp_ResponseCode'] ?? ''),
            'raw' => $response,
        ];
    }

    private function buildQuery(array $params): string
    {
        ksort($params);

        return http_build_query($params, '', '&', PHP_QUERY_RFC1738);
    }

    private function hash(array $params): string
    {
        return hash_hmac('sha512', $this->buildQuery($params), $this->config('hash_secret'));
    }

    private function config(string $key): string
    {
        $value = (string) config('payment.gateways.vnpay.' . $key);
        if ($value === '') {
            throw new RuntimeException('VNPay chưa được cấu hình ' . $key . '.');
        }

        return $value;
    }
}