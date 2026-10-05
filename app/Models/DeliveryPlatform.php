<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPlatform extends Model
{
    public const KINDS = [
        'internal' => 'Interne',
        'external' => 'Plateforme externe',
    ];

    protected $fillable = [
        'company_id', 'name', 'code', 'kind', 'is_active', 'is_delivery_agent',
        'commission_type', 'commission_value',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_delivery_agent' => 'boolean',
            'commission_value' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
