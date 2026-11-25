<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Giải phóng ghế hết hạn và đối soát giao dịch pending với cổng thanh toán (bắt webhook thất lạc)';

    public function handle(PaymentService $payments): int
    {
        $expired = Booking::releaseExpiredHolds();
        $this->info("Đã giải phóng {$expired} đơn giữ ghế hết hạn.");

        $reconciled = 0;
        Payment::where('status', Payment::STATUS_PENDING)
            ->where('created_at', '<=', now()->subMinutes(2))
            ->chunkById(100, function ($batch) use ($payments, &$reconciled) {
                foreach ($batch as $payment) {
                    $payments->reconcile($payment);
                    $reconciled++;
                }
            });

        $this->info("Đã đối soát {$reconciled} giao dịch pending.");

        return self::SUCCESS;
    }
}