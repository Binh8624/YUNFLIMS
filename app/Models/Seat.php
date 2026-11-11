<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    protected $fillable = [
        'room_id',
        'seat_code',
        'row_number',
        'seat_number',
        'seat_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'row_number' => 'integer',
            'seat_number' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }
}
