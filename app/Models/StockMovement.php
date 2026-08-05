<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPES = [
        'in' => 'Entrée',
        'out' => 'Sortie',
        'adjustment' => 'Ajustement',
        'transfer' => 'Transfert',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'product_id',
        'product_variant_id',
        'user_id',
        'inventory_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'reference',
        'comment',
        'related_store_id',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'quantity_before' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'moved_at' => 'datetime',
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

    public function relatedStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'related_store_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(StockInventory::class, 'inventory_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function signedQuantity(): float
    {
        return match ($this->type) {
            'in' => abs((float) $this->quantity),
            'out' => -abs((float) $this->quantity),
            default => (float) $this->quantity,
        };
    }
}
