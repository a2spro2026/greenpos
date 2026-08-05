<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaasSubscription extends Model
{
    protected $table = 'saas_subscriptions';

    public const STATUSES = [
        'trialing' => 'Essai gratuit',
        'active' => 'Actif',
        'suspended' => 'Suspendu',
        'expired' => 'Expiré',
        'cancelled' => 'Résilié',
        'past_due' => 'En attente de paiement',
    ];

    public const STATUS_COLORS = [
        'trialing' => 'bg-sky-500/15 text-sky-300',
        'active' => 'bg-emerald-500/15 text-emerald-300',
        'suspended' => 'bg-amber-500/15 text-amber-300',
        'expired' => 'bg-rose-500/15 text-rose-300',
        'cancelled' => 'bg-slate-500/15 text-slate-300',
        'past_due' => 'bg-orange-500/15 text-orange-300',
    ];

    protected $fillable = [
        'saas_tenant_id', 'saas_plan_id', 'status', 'billing_cycle',
        'amount', 'currency', 'starts_at', 'trial_ends_at', 'converted_at',
        'ends_at', 'renews_at', 'last_reminder_at',
        'cancelled_at', 'suspended_at', 'suspend_reason', 'cancel_reason',
        'notes', 'renewal_count', 'provider', 'provider_subscription_id', 'auto_renew', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'converted_at' => 'datetime',
            'ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'last_reminder_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'suspended_at' => 'datetime',
            'auto_renew' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(SaasTenant::class, 'saas_tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SaasPayment::class, 'saas_subscription_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(SaasLicense::class, 'saas_subscription_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(SaasSubscriptionAlert::class, 'saas_subscription_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-500/15 text-slate-300';
    }

    public function monthlyEquivalent(): float
    {
        if ($this->billing_cycle === 'yearly') {
            return round((float) $this->amount / 12, 2);
        }

        return (float) $this->amount;
    }

    public function isExpiringSoon(int $days = 14): bool
    {
        return $this->ends_at
            && $this->ends_at->isFuture()
            && $this->ends_at->lte(now()->addDays($days))
            && in_array($this->status, ['active', 'trialing'], true);
    }

    public function daysRemaining(): ?int
    {
        $end = $this->status === 'trialing' && $this->trial_ends_at
            ? $this->trial_ends_at
            : $this->ends_at;

        if (! $end) {
            return null;
        }

        return (int) now()->diffInDays($end, false);
    }

    public function trialDaysRemaining(): ?int
    {
        if ($this->status !== 'trialing') {
            return null;
        }

        $end = $this->trial_ends_at ?: $this->ends_at;
        if (! $end) {
            return null;
        }

        return max(0, (int) now()->diffInDays($end, false));
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SaasInvoice::class, 'saas_subscription_id');
    }
}
