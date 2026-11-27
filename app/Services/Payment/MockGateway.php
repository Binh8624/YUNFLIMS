<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Support\Str;

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
        $payment->loadMissing('booking');
        $booking = $payment->booking;
        $expired = $booking
            && $booking->status === 'pending'
            && $booking->hold_expires_at
            && ! $booking->hold_expires_at->isFuture();

        return [
            'signature_valid' => true,
            'status' => $expired ? Payment::STATUS_TIMEOUT : Payment::STATUS_PENDING,
            'order_ref' => $payment->order_ref,
            'transaction_code' => $payment->transaction_code,
            'amount' => (float) $payment->amount,
            'message' => $expired
                ? 'Mock gateway giữ trạng thái timeout vì thời gian giữ ghế đã hết'
                : 'Mock gateway không có giao dịch chờ đối soát',
            'raw' => [
                'order_ref' => $payment->order_ref,
                'status' => $expired ? 'timeout' : 'pending',
            ],
        ];
    }

    public function refund(Payment $payment, PaymentRefund $refund): array
    {
        return [
            'signature_valid' => true,
            'status' => PaymentRefund::STATUS_SUCCEEDED,
            'gateway_refund_id' => 'MOCKRF-' . strtoupper(Str::random(10)),
            'message' => 'Mock gateway hoàn tiền thành công',
            'raw' => ['order_ref' => $payment->order_ref, 'refund_id' => $refund->id, 'status' => 'succeeded'],
        ];
    }

    private function secret(): string
    {
        return (string) (config('payment.gateways.mock.secret') ?: hash('sha256', (string) config('app.key')));
    }
}