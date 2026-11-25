<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcessionOrderItem extends Model
{
    protected $fillable = [
        'concession_order_id',
        'concession_product_id',
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ConcessionOrder::class, 'concession_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ConcessionProduct::class, 'concession_product_id');
    }
}