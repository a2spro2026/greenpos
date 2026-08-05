<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInventoryLine extends Model
{
    protected $fillable = [
        'inventory_id',
        'product_id',
        'expected_qty',
        'counted_qty',
        'difference',
        'is_counted',
    ];

    protected function casts(): array
    {
        return [
            'expected_qty' => 'decimal:3',
            'counted_qty' => 'decimal:3',
            'difference' => 'decimal:3',
            'is_counted' => 'boolean',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(StockInventory::class, 'inventory_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
