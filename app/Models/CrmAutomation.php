<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmAutomation extends Model
{
    public const TRIGGERS = [
        'low_stock' => 'Stock bas',
        'sales_threshold' => 'Seuil de ventes',
        'production' => 'Production',
        'time_based' => 'Planifié',
        'custom' => 'Personnalisé',
    ];

    protected $fillable = [
        'company_id', 'name', 'trigger_type', 'is_active',
        'conditions', 'actions', 'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'conditions' => 'array',
            'actions' => 'array',
            'last_run_at' => 'datetime',
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
}
