<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLoss extends Model
{
    public const TYPES = [
        'loss' => 'Perte',
        'breakage' => 'Casse',
        'expiration' => 'Péremption',
        'theft' => 'Vol',
    ];

    public const TYPE_COLORS = [
        'loss' => 'bg-amber-100 text-amber-800',
        'breakage' => 'bg-orange-100 text-orange-800',
        'expiration' => 'bg-rose-100 text-rose-800',
        'theft' => 'bg-red-100 text-red-800',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'created_by',
        'employee_id',
        'responsible_name',
        'number',
        'loss_type',
        'loss_date',
        'total_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loss_date' => 'date',
            'total_cost' => 'decimal:2',
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

    public function lines(): HasMany
    {
        return $this->hasMany(StockLossLine::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->loss_type] ?? $this->loss_type;
    }

    public function typeColor(): string
    {
        return self::TYPE_COLORS[$this->loss_type] ?? 'bg-slate-100 text-slate-700';
    }
}
