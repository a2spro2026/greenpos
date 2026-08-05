<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnLine extends Model
{
    protected $fillable = ['sale_return_id', 'sale_line_id', 'product_id', 'quantity', 'unit_refund', 'line_refund'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_refund' => 'decimal:2', 'line_refund' => 'decimal:2'];
    }

    public function saleReturn(): BelongsTo { return $this->belongsTo(SaleReturn::class); }
    public function saleLine(): BelongsTo { return $this->belongsTo(SaleLine::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
