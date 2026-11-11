<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    protected $fillable = [
        'name', 'seat_type', 'format_id', 'day_type',
        'time_start', 'time_end', 'amount_type', 'amount',
        'priority', 'is_active',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(Format::class);
    }
}
