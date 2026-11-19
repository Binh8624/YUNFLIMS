<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'showtime_id',
        'booking_code',
        'total_amount',
        'status',
        'payment_method',
        'payment_status',
        'payment_reference',
        'paid_at',
        'qr_code',
        'hold_expires_at',
        'ticket_status',
        'ticket_used_at',
        'checkin_count',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'ticket_used_at' => 'datetime',
        ];
    }

    public static function generateBookingCode(): string
    {
        do {
            $code = 'YF-' . strtoupper(Str::random(12));
        } while (self::where('booking_code', $code)->exists());

        return $code;
    }

    public static function generateQrSvg(string $value): string
    {
        return Builder::create()
            ->writer(new SvgWriter)
            ->data(route('tickets.verify', ['bookingCode' => $value]))
            ->size(240)
            ->margin(10)
            ->build()
            ->getString();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }
}