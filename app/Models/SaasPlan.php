<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaasPlan extends Model
{
    protected $table = 'saas_plans';

    public const SUPPORT_LEVELS = [
        'none' => 'Aucun',
        'email' => 'Email',
        'chat' => 'Chat',
        'priority' => 'Prioritaire',
        'dedicated' => 'Dédié',
    ];

    protected $fillable = [
        'code', 'name', 'tagline', 'description',
        'price_monthly', 'price_yearly', 'currency',
        'max_users', 'max_stores', 'storage_gb',
        'api_enabled', 'support_included', 'support_level',
        'backups_enabled', 'custom_domain_enabled', 'trial_days',
        'modules', 'features', 'is_public', 'is_active', 'sort_order',
        'stripe_price_monthly', 'stripe_price_yearly',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'modules' => 'array',
            'features' => 'array',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'api_enabled' => 'boolean',
            'support_included' => 'boolean',
            'backups_enabled' => 'boolean',
            'custom_domain_enabled' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SaasSubscription::class, 'saas_plan_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function priceLabel(string $cycle = 'monthly'): string
    {
        $amount = $cycle === 'yearly' ? $this->price_yearly : $this->price_monthly;

        return number_format((float) $amount, 2, ',', ' ').' '.$this->currency;
    }

    public function supportLabel(): string
    {
        return self::SUPPORT_LEVELS[$this->support_level] ?? ($this->support_level ?: '—');
    }
}
