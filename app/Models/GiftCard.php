<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCard extends Model
{
    protected $fillable = [
        'code',
        'name',
        'initial_balance',
        'balance',
        'purchased_by',
        'assigned_to',
        'status',
        'starts_at',
        'expires_at',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'balance' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class);
    }
}