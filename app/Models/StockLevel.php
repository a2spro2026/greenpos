<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    public const STATUSES = [
        'ok' => 'Disponible',
        'low' => 'Faible',
        'out' => 'Rupture',
        'over' => 'Surstock',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'product_id',
        'quantity',
        'min_quantity',
        'max_quantity',
        'reserved_quantity',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'min_quantity' => 'decimal:3',
            'max_quantity' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'last_movement_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function availableQuantity(): float
    {
        return (float) $this->quantity - (float) $this->reserved_quantity;
    }

    public function stockStatus(): string
    {
        $qty = (float) $this->quantity;
        $min = (float) $this->min_quantity;
        $max = $this->max_quantity !== null ? (float) $this->max_quantity : null;

        if ($qty <= 0) {
            return 'out';
        }
        if ($min > 0 && $qty <= $min) {
            return 'low';
        }
        if ($max !== null && $max > 0 && $qty > $max) {
            return 'over';
        }

        return 'ok';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->stockStatus()] ?? $this->stockStatus();
    }

    public function valuation(): float
    {
        $cost = (float) ($this->product?->purchase_price ?? 0);

        return (float) $this->quantity * $cost;
    }
}
