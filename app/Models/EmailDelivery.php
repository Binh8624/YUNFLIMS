<?php

namespace App\Models;

use App\Jobs\SendTransactionalEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDelivery extends Model
{
    protected $fillable = [
        'idempotency_key',
        'type',
        'user_id',
        'booking_id',
        'showtime_id',
        'recipient_email',
        'status',
        'attempts',
        'last_error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public static function dispatchOnce(
        string $type,
        User $user,
        ?Booking $booking = null,
        ?Showtime $showtime = null
    ): self {
        $key = hash('sha256', implode('|', [
            $type,
            $user->getKey(),
            $booking?->getKey() ?? '',
            $showtime?->getKey() ?? '',
        ]));

        $delivery = self::firstOrCreate(
            ['idempotency_key' => $key],
            [
                'type' => $type,
                'user_id' => $user->getKey(),
                'booking_id' => $booking?->getKey(),
                'showtime_id' => $showtime?->getKey(),
                'recipient_email' => $user->email,
                'status' => 'pending',
            ]
        );

        SendTransactionalEmail::dispatch($delivery->getKey())->afterCommit();

        return $delivery;
    }
}
