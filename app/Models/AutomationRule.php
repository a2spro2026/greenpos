<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRule extends Model
{
    public const TRIGGERS = [
        'low_stock' => 'Stock faible',
        'sales_threshold' => 'Seuil de ventes',
        'production_event' => 'Événement production',
        'time_based' => 'Planifié',
        'custom' => 'Personnalisé',
    ];

    protected $fillable = [
        'company_id', 'name', 'trigger', 'is_active', 'config', 'last_ran_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
            'last_ran_at' => 'datetime',
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

    public function triggerLabel(): string
    {
        return self::TRIGGERS[$this->trigger] ?? $this->trigger;
    }
}
