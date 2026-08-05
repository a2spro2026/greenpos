<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLine extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'product_name', 'sku', 'description',
        'quantity', 'unit_price', 'discount_percent', 'tax_rate',
        'line_subtotal', 'line_tax', 'line_total', 'returned_quantity', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3', 'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2', 'tax_rate' => 'decimal:2',
            'line_subtotal' => 'decimal:2', 'line_tax' => 'decimal:2',
            'line_total' => 'decimal:2', 'returned_quantity' => 'decimal:3',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function returnableQuantity(): float
    {
        return max(0, (float) $this->quantity - (float) $this->returned_quantity);
    }
}
