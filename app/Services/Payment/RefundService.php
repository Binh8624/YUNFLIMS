<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\LoyaltyPointTransaction;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function request(Booking $booking, User $requester, ?string $reason = null): PaymentRefund
    {
        return DB::transaction(function () use ($booking, $requester, $reason) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $lockedBooking->load('showtime');

            if ($lockedBooking->status !== 'confirmed' || $lockedBooking->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'booking' => 'Chỉ đơn đã thanh toán thành công mới thực hiện hoàn tiền.',
                ]);
            }

            $cutoffHours = (float) config('payment.refund_cutoff_hours', 0);
            if (now()->greaterThanOrEqualTo($lockedBooking->showtime->starts_at->subHours($cutoffHours))) {
                throw ValidationException::withMessages([
                    'booking' => 'Đã quá thời hạn hủy vé theo chính sách (trước giờ chiếu ' . $cutoffHours . ' giờ).',
                ]);
            }

            if ($lockedBooking->ticket_used_at !== null || (int) $lockedBooking->checkin_count > 0) {
                throw ValidationException::withMessages([
                    'booking' => 'Vé đã check-in, không thể hủy hoặc hoàn tiền.',
                ]);
            }

            $payment = Payment::where('booking_id', $lockedBooking->id)
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->lockForUpdate()
                ->first();
            if (! $payment) {
                throw ValidationException::withMessages([
                    'booking' => 'Không tìm thấy giao dịch thành công để hoàn tiền.',
                ]);
            }

            $activeRefund = PaymentRefund::where('payment_id', $payment->id)
                ->whereIn('status', [
                    PaymentRefund::STATUS_REQUESTED,
                    PaymentRefund::STATUS_PROCESSING,
                    PaymentRefund::STATUS_SUCCEEDED,
                ])
                ->exists();
            if ($activeRefund) {
                throw ValidationException::withMessages([
                    'booking' => 'Đơn này đã có yêu cầu hoàn tiền, không thể hoàn lần hai.',
                ]);
            }

            $refund = PaymentRefund::create([
                'payment_id' => $payment->id,
                'booking_id' => $lockedBooking->id,
                'amount' => $payment->amount,
                'status' => PaymentRefund::STATUS_REQUESTED,
                'idempotency_key' => (string) Str::uuid(),
                'reason' => $reason,
                'requested_by' => $requester->id,
                'requested_at' => now(),
            ]);

            $result = $this->payments->gateway($payment->gateway)->refund($payment, $refund);

            if (! $result['signature_valid'] || $result['status'] !== PaymentRefund::STATUS_SUCCEEDED) {
                $refund->update([
                    'status' => PaymentRefund::STATUS_FAILED,
                    'gateway_response' => $result['raw'],
                    'processed_at' => now(),
                ]);

                return $refund;
            }

            $refund->update([
                'status' => PaymentRefund::STATUS_SUCCEEDED,
                'gateway_refund_id' => $result['gateway_refund_id'],
                'gateway_response' => $result['raw'],
                'processed_at' => now(),
            ]);
            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'refunded_at' => now(),
            ]);
            $lockedBooking->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'ticket_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $requester->id,
                'cancel_reason' => $reason,
            ]);
            BookingSeat::where('booking_id', $lockedBooking->id)
                ->whereNull('released_at')
                ->update(['released_at' => now()]);

            $this->restockConcessionOrder($lockedBooking);
            $this->revokeLoyalty($lockedBooking);

            return $refund;
        });
    }



    private function restockConcessionOrder(Booking $booking): void
    {
        $concessionOrder = \App\Models\ConcessionOrder::where('booking_id', $booking->id)
            ->lockForUpdate()
            ->first();

        if (! $concessionOrder || $concessionOrder->status === 'cancelled') {
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
                if ($product && $quantity > 0) {
                    $product->increment('stock_quantity', $quantity);
                }
            }
        }

        $concessionOrder->update([
            'status' => 'cancelled',
            'payment_status' => 'refunded',
        ]);
    }
    private function revokeLoyalty(Booking $booking): void
    {
        if (LoyaltyPointTransaction::where('booking_id', $booking->id)
            ->where('type', LoyaltyPointTransaction::TYPE_REVOKE)
            ->exists()) {
            return;
        }

        $earn = LoyaltyPointTransaction::where('booking_id', $booking->id)
            ->where('type', LoyaltyPointTransaction::TYPE_EARN)
            ->first();
        $points = $earn?->points ?? (int) $booking->points_earned;
        if ($points <= 0) {
            return;
        }

        $user = User::whereKey($booking->user_id)->lockForUpdate()->first();
        if (! $user) {
            return;
        }

        $user->loyalty_points = ($user->loyalty_points ?? 0) - $points;
        $user->member_tier = User::resolveMemberTier($user->loyalty_points);
        $user->save();

        LoyaltyPointTransaction::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'type' => LoyaltyPointTransaction::TYPE_REVOKE,
            'points' => $points,
            'balance_after' => $user->loyalty_points,
            'note' => 'Thu hồi điểm do hoàn tiền đơn ' . $booking->booking_code,
        ]);
    }
}