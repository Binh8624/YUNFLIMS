<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\Payment\PaymentService;

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
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'points_earned',
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
            ->data($value)
            ->size(240)
            ->margin(10)
            ->build()
            ->getString();
    }

    public static function releaseExpiredHolds(): int
    {
        $expiredIds = self::where('status', 'pending')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->orderBy('id')
            ->pluck('id');

        if ($expiredIds->isEmpty()) {
            return 0;
        }

        $released = 0;

        foreach ($expiredIds as $bookingId) {
            $mockPaymentTimedOut = false;
            $mockPayment = Payment::where('booking_id', $bookingId)
                ->where('gateway', 'mock')
                ->where('status', Payment::STATUS_PENDING)
                ->first();

            if ($mockPayment) {
                app(PaymentService::class)->reconcile($mockPayment);
                $mockPaymentTimedOut = $mockPayment->fresh()->status === Payment::STATUS_TIMEOUT;
            }

            $wasReleased = DB::transaction(function () use ($bookingId): bool {
                $booking = self::whereKey($bookingId)->lockForUpdate()->first();

                if (
                    ! $booking
                    || $booking->status !== 'pending'
                    || ! $booking->hold_expires_at
                    || $booking->hold_expires_at->isFuture()
                    || $booking->payment_status === 'paid'
                    || Payment::where('booking_id', $booking->id)
                        ->where('status', Payment::STATUS_PENDING)
                        ->exists()
                ) {
                    return false;
                }

                $booking->update([
                    'status' => 'cancelled',
                    'payment_status' => 'timeout',
                    'ticket_status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
                BookingSeat::where('booking_id', $booking->id)
                    ->whereNull('released_at')
                    ->update(['released_at' => now()]);
                \App\Models\ConcessionOrder::where('booking_id', $booking->id)
                    ->whereIn('payment_status', ['pending', 'unpaid'])
                    ->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancelled',
                    ]);

                return true;
            });

            $released += (int) ($wasReleased || $mockPaymentTimedOut);
            if ($wasReleased) {
                $booking = self::with('user')->find($bookingId);
                if ($booking?->user) {
                    EmailDelivery::dispatchOnce('booking_cancelled', $booking->user, $booking);
                }
            }
        }

        return $released;
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

    public function concessionOrder(): HasOne
    {
        return $this->hasOne(ConcessionOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function ticketCheckins(): HasMany
    {
        return $this->hasMany(TicketCheckin::class);
    }
}