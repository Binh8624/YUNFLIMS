<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCheckin extends Model
{
    protected $fillable = [
        'booking_id',
        'checked_by',
        'cinema_id',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function cinema(): BelongsTo
    {
        return $this->belongsTo(Cinema::class);
    }
}
