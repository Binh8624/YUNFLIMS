<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Giải phóng ghế hết hạn và đối soát giao dịch pending với cổng thanh toán (bắt webhook thất lạc)';

    public function handle(PaymentService $payments): int
    {
        $reconciled = 0;
        $failed = 0;
        Payment::where('status', Payment::STATUS_PENDING)
            ->where('created_at', '<=', now()->subMinutes(2))
            ->chunkById(100, function ($batch) use ($payments, &$reconciled, &$failed) {
                foreach ($batch as $payment) {
                    try {
                        $payments->reconcile($payment);
                        $reconciled++;
                    } catch (Throwable $exception) {
                        $failed++;
                        Log::error('Payment reconciliation failed.', [
                            'payment_id' => $payment->id,
                            'gateway' => $payment->gateway,
                            'exception' => $exception->getMessage(),
                        ]);
                        $this->error("Không thể đối soát payment #{$payment->id}.");
                    }
                }
            });

        $expired = Booking::releaseExpiredHolds();
        $this->info("Đã đối soát {$reconciled} giao dịch pending; giải phóng {$expired} đơn giữ ghế hết hạn.");
        Log::info('Payment reconciliation run completed.', [
            'payments_reconciled' => $reconciled,
            'payments_failed' => $failed,
            'expired_bookings_released' => $expired,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}