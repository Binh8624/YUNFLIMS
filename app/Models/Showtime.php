<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Showtime extends Model
{
    protected $fillable = [
        'movie_id',
        'room_id',
        'format_id',
        'starts_at',
        'ends_at',
        'price',
        'vip_price',
        'couple_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'price' => 'decimal:2',
            'vip_price' => 'decimal:2',
            'couple_price' => 'decimal:2',
        ];
    }

    public function basePriceForSeatType(string $seatType): float
    {
        return match ($seatType) {
            'vip' => (float) ($this->vip_price ?? $this->price),
            'couple' => (float) ($this->couple_price ?? $this->price),
            default => (float) $this->price,
        };
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(Format::class);
    }

    /**
     * Các đơn đặt vé của suất chiếu.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Các ghế được đặt trong suất chiếu.
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }
}