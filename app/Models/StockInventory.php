<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockInventory extends Model
{
    public const STATUSES = [
        'draft' => 'Brouillon',
        'in_progress' => 'En cours',
        'validated' => 'Validé',
        'cancelled' => 'Annulé',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'created_by',
        'validated_by',
        'name',
        'status',
        'notes',
        'started_at',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'validated_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockInventoryLine::class, 'inventory_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'inventory_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function varianceCount(): int
    {
        return $this->lines->filter(fn (StockInventoryLine $line) => $line->is_counted && (float) $line->difference !== 0.0)->count();
    }
}
