<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasSubscriptionAlert extends Model
{
    protected $table = 'saas_subscription_alerts';

    public const TYPES = [
        'expiring_soon' => 'Expiration proche',
        'payment_failed' => 'Paiement échoué',
        'renewed' => 'Renouvellement réussi',
        'renewal_reminder' => 'Rappel renouvellement',
        'limit_exceeded' => 'Limite dépassée',
        'suspended' => 'Suspension',
        'cancelled' => 'Résiliation',
        'upgraded' => 'Montée en gamme',
        'downgraded' => 'Descente de gamme',
        'plan_changed' => 'Changement de plan',
        'trial_converted' => 'Essai converti',
        'trial_expired' => 'Essai expiré',
    ];

    public const SEVERITIES = [
        'info' => 'Information',
        'warning' => 'Avertissement',
        'critical' => 'Critique',
    ];

    protected $fillable = [
        'saas_subscription_id', 'saas_tenant_id', 'type', 'severity',
        'title', 'body', 'is_read', 'read_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SaasSubscription::class, 'saas_subscription_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(SaasTenant::class, 'saas_tenant_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function severityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'bg-rose-500/15 text-rose-300',
            'warning' => 'bg-amber-500/15 text-amber-300',
            default => 'bg-sky-500/15 text-sky-300',
        };
    }
}
