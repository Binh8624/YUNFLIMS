<?php

namespace App\Services\Payment;

use App\Models\Payment;

class MockGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'mock';
    }

    public function createPaymentUrl(Payment $payment): string
    {
        return route('payment.mock.checkout', $payment);
    }

    public function sign(array $payload): string
    {
        unset($payload['signature']);
        ksort($payload);

        return hash_hmac('sha256', http_build_query($payload, '', '&', PHP_QUERY_RFC1738), $this->secret());
    }

    public function verifyCallback(array $payload): array
    {
        $signature = (string) ($payload['signature'] ?? '');
        $status = match ((string) ($payload['status'] ?? '')) {
            'success' => Payment::STATUS_SUCCEEDED,
            'failed' => Payment::STATUS_FAILED,
            'timeout' => Payment::STATUS_TIMEOUT,
            default => Payment::STATUS_PENDING,
        };

        return [
            'signature_valid' => $signature !== '' && hash_equals($this->sign($payload), $signature),
            'status' => $status,
            'order_ref' => isset($payload['order_ref']) ? (string) $payload['order_ref'] : null,
            'transaction_code' => isset($payload['transaction_code']) ? (string) $payload['transaction_code'] : null,
            'amount' => isset($payload['amount']) ? (float) $payload['amount'] : null,
            'message' => 'Mock gateway callback',
        ];
    }

    public function queryTransaction(Payment $payment): array
    {
        return [
            'signature_valid' => true,
            'status' => Payment::STATUS_PENDING,
            'order_ref' => $payment->order_ref,
            'transaction_code' => $payment->transaction_code,
            'amount' => (float) $payment->amount,
            'message' => 'Mock gateway không có giao dịch chờ đối soát',
            'raw' => ['order_ref' => $payment->order_ref, 'status' => 'pending'],
        ];
    }

    private function secret(): string
    {
        return (string) (config('payment.gateways.mock.secret') ?: hash('sha256', (string) config('app.key')));
    }
}