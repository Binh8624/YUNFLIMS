<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface PaymentGateway
{
    public function name(): string;

    public function createPaymentUrl(Payment $payment): string;

    /**
     * @return array{signature_valid: bool, status: string, order_ref: ?string, transaction_code: ?string, amount: ?float, message: ?string}
     */
    public function verifyCallback(array $payload): array;
}