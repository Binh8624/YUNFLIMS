<?php

namespace App\Jobs;

use App\Mail\TransactionalEmail;
use App\Models\EmailDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTransactionalEmail implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 60;

    public int $uniqueFor = 86400;

    public function __construct(public int $deliveryId)
    {
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function uniqueId(): string
    {
        return (string) $this->deliveryId;
    }

    public function handle(): void
    {
        $delivery = EmailDelivery::findOrFail($this->deliveryId);
        if ($delivery->status === 'sent') {
            return;
        }

        $delivery->increment('attempts');

        try {
            Mail::to($delivery->recipient_email)->send(new TransactionalEmail($delivery));
            $delivery->update([
                'status' => 'sent',
                'last_error' => null,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $delivery->update(['last_error' => $exception->getMessage()]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        EmailDelivery::whereKey($this->deliveryId)->update([
            'status' => 'failed',
            'last_error' => $exception->getMessage(),
        ]);

        Log::error('Transactional email delivery failed permanently.', [
            'delivery_id' => $this->deliveryId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
