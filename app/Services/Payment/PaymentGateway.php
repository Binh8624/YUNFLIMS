<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentRefund;

interface PaymentGateway
{
    public function name(): string;

    public function createPaymentUrl(Payment $payment): string;

    /**
     * @return array{signature_valid: bool, status: string, order_ref: ?string, transaction_code: ?string, amount: ?float, message: ?string}
     */
    public function verifyCallback(array $payload): array;

    /**
     * Truy vấn trạng thái giao dịch phía cổng (server-to-server) để đối soát webhook thất lạc.
     *
     * @return array{signature_valid: bool, status: string, order_ref: ?string, transaction_code: ?string, amount: ?float, message: ?string, raw: array}
     */
    public function queryTransaction(Payment $payment): array;

    /**
     * @return array{signature_valid: bool, status: string, gateway_refund_id: ?string, message: ?string, raw: array}
     */
    public function refund(Payment $payment, PaymentRefund $refund): array;
}