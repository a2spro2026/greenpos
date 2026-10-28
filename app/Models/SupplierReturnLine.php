<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReturnLine extends Model
{
    protected $fillable = ['supplier_return_id', 'purchase_receipt_line_id', 'product_id', 'quantity', 'unit_price', 'tax_rate', 'line_total'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'tax_rate' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function supplierReturn(): BelongsTo { return $this->belongsTo(SupplierReturn::class); }
}
