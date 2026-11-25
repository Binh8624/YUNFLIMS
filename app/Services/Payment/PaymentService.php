<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\LoyaltyPointTransaction;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PaymentService
{
    public function gateway(string $name): PaymentGateway
    {
        if (! in_array($name, array_column($this->enabledGateways(), 'name'), true)) {
            throw new InvalidArgumentException('Cổng thanh toán không khả dụng: ' . $name);
        }

        return match ($name) {
            'mock' => new MockGateway,
            'vnpay' => new VnpayGateway,
        };
    }

    /**
     * @return array<int, array{name: string, label: string, methods: array<string, string>}>
     */
    public function enabledGateways(): array
    {
        $gateways = [];
        foreach (config('payment.gateways', []) as $name => $gateway) {
            $enabled = (bool) ($gateway['enabled'] ?? false);
            if ($name === 'vnpay') {
                $enabled = $enabled && ! empty($gateway['tmn_code']) && ! empty($gateway['hash_secret']);
            }
            if ($enabled) {
                $gateways[] = [
                    'name' => $name,
                    'label' => $gateway['label'] ?? $name,
                    'methods' => $gateway['methods'] ?? [],
                ];
            }
        }

        return $gateways;
    }

    public function initiate(Booking $booking, string $gatewayName, ?string $method = null): Payment
    {
        return DB::transaction(function () use ($booking, $gatewayName, $method) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->status !== 'pending' || $lockedBooking->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'payment' => 'Đơn đặt vé này không còn ở trạng thái chờ thanh toán.',
                ]);
            }

            if (! $lockedBooking->hold_expires_at || $lockedBooking->hold_expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'payment' => 'Thời gian giữ ghế đã hết. Vui lòng đặt lại ghế.',
                ]);
            }

            $existing = Payment::where('booking_id', $lockedBooking->id)
                ->where('gateway', $gatewayName)
                ->where('status', Payment::STATUS_PENDING)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            return Payment::create([
                'booking_id' => $lockedBooking->id,
                'gateway' => $gatewayName,
                'method' => $method,
                'order_ref' => 'YF' . $lockedBooking->id . '-' . strtoupper(Str::random(10)),
                'amount' => $lockedBooking->total_amount,
                'currency' => 'VND',
                'status' => Payment::STATUS_PENDING,
                'idempotency_key' => (string) Str::uuid(),
                'request_payload' => [
                    'booking_code' => $lockedBooking->booking_code,
                    'initiated_at' => now()->toIso8601String(),
                ],
            ]);
        });
    }

    public function handleCallback(string $gatewayName, array $payload, string $source): PaymentEvent
    {
        $result = $this->gateway($gatewayName)->verifyCallback($payload);

        return $this->processEvent($gatewayName, $payload, $result, $source);
    }

    public function reconcile(Payment $payment): PaymentEvent
    {
        $result = $this->gateway($payment->gateway)->queryTransaction($payment);

        return $this->processEvent($payment->gateway, $result['raw'], $result, 'reconcile');
    }

    private function processEvent(string $gatewayName, array $payload, array $result, string $source): PaymentEvent
    {
        $eventHash = hash('sha256', $gatewayName . '|' . $source . '|' . json_encode($this->canonical($payload)));

        return DB::transaction(function () use ($gatewayName, $payload, $result, $eventHash) {
            $event = PaymentEvent::where('event_hash', $eventHash)->lockForUpdate()->first();
            if (! $event) {
                $event = PaymentEvent::create([
                    'gateway' => $gatewayName,
                    'event_hash' => $eventHash,
                    'signature_valid' => $result['signature_valid'],
                    'raw_payload' => $payload,
                ]);
            }

            if ($event->processed_at !== null) {
                return $event;
            }

            $finish = function () use ($event) {
                $event->processed_at = now();
                $event->save();

                return $event;
            };

            if ($result['order_ref'] === null) {
                return $finish();
            }

            $payment = Payment::where('order_ref', $result['order_ref'])->first();
            if (! $payment) {
                return $finish();
            }
            $event->payment_id = $payment->id;
            $event->save();

            if (! $result['signature_valid']) {
                return $finish();
            }

            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->status !== Payment::STATUS_PENDING) {
                return $finish();
            }

            if ($result['amount'] === null || abs($result['amount'] - (float) $payment->amount) > 0.009) {
                return $finish();
            }

            $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            match ($result['status']) {
                Payment::STATUS_SUCCEEDED => $this->markSucceeded($payment, $booking, $result, $payload),
                Payment::STATUS_FAILED => $this->markFailed($payment, $booking, Payment::STATUS_FAILED, $payload),
                Payment::STATUS_TIMEOUT => $this->markFailed($payment, $booking, Payment::STATUS_TIMEOUT, $payload),
                default => null,
            };

            return $finish();
        });
    }

    private function markSucceeded(Payment $payment, Booking $booking, array $result, array $payload): void
    {
        $payment->update([
            'status' => Payment::STATUS_SUCCEEDED,
            'transaction_code' => $result['transaction_code'],
            'paid_at' => now(),
            'gateway_response' => $payload,
        ]);

        $booking->update([
            'payment_status' => 'paid',
            'payment_method' => $payment->method ?? $payment->gateway,
            'payment_reference' => $payment->order_ref,
            'paid_at' => now(),
            'status' => 'confirmed',
            'ticket_status' => 'available',
        ]);

        $this->confirmConcessionOrder($booking, $payment);
        $this->awardLoyalty($booking);
    }

    private function markFailed(Payment $payment, Booking $booking, string $status, array $payload): void
    {
        $payment->update([
            'status' => $status,
            'gateway_response' => $payload,
        ]);

        $booking->update([
            'payment_status' => $status,
            'status' => 'cancelled',
            'ticket_status' => 'cancelled',
        ]);

        BookingSeat::where('booking_id', $booking->id)
            ->whereNull('released_at')
            ->update(['released_at' => now()]);

        $concessionOrder = \App\Models\ConcessionOrder::where('booking_id', $booking->id)->lockForUpdate()->first();
        if ($concessionOrder && in_array($concessionOrder->payment_status, ['pending', 'unpaid'], true)) {
            $concessionOrder->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
        }
    }

    private function awardLoyalty(Booking $booking): void
    {
        if (LoyaltyPointTransaction::where('booking_id', $booking->id)
            ->where('type', LoyaltyPointTransaction::TYPE_EARN)
            ->exists()) {
            return;
        }

        $user = User::whereKey($booking->user_id)->lockForUpdate()->first();
        if (! $user) {
            return;
        }

        $points = max(10, (int) round((float) $booking->total_amount / 10000));
        $user->loyalty_points = ($user->loyalty_points ?? 0) + $points;
        $user->member_tier = User::resolveMemberTier($user->loyalty_points);
        $user->save();

        LoyaltyPointTransaction::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'type' => LoyaltyPointTransaction::TYPE_EARN,
            'points' => $points,
            'balance_after' => $user->loyalty_points,
            'note' => 'Tích điểm thanh toán đơn ' . $booking->booking_code,
        ]);

        $booking->update(['points_earned' => $points]);
    }



    private function confirmConcessionOrder(Booking $booking, Payment $payment): void
    {
        $concessionOrder = \App\Models\ConcessionOrder::where('booking_id', $booking->id)
            ->lockForUpdate()
            ->first();

        if (! $concessionOrder) {
            return;
        }

        if (in_array($concessionOrder->status, ['confirmed', 'completed', 'preparing'], true)
            && $concessionOrder->payment_status === 'paid') {
            return;
        }

        $concessionOrder->load('items');
        $quantities = $concessionOrder->items
            ->filter(fn ($item) => $item->concession_product_id !== null)
            ->groupBy('concession_product_id')
            ->map(fn ($group) => (int) $group->sum('quantity'));

        if ($quantities->isNotEmpty()) {
            $products = \App\Models\ConcessionProduct::query()
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                if ($product && $product->is_active && $product->stock_quantity >= $quantity) {
                    $product->decrement('stock_quantity', $quantity);
                }
            }
        }

        $concessionOrder->update([
            'status' => 'confirmed',
            'payment_method' => $payment->method ?? $payment->gateway,
            'payment_status' => 'paid',
            'payment_reference' => $payment->order_ref,
            'paid_at' => now(),
        ]);
    }
    private function canonical(array $payload): array
    {
        ksort($payload);
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->canonical($value);
            }
        }

        return $payload;
    }
}