<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaasPayment extends Model
{
    protected $table = 'saas_payments';

    public const PROVIDERS = [
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'cmi' => 'CMI',
        'manual' => 'Manuel',
    ];

    public const STATUSES = [
        'pending' => 'En attente',
        'paid' => 'Payé',
        'failed' => 'Échoué',
        'refunded' => 'Remboursé',
    ];

    protected $fillable = [
        'saas_tenant_id', 'saas_subscription_id', 'number', 'provider',
        'provider_payment_id', 'status', 'amount', 'currency', 'description', 'paid_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(SaasTenant::class, 'saas_tenant_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SaasSubscription::class, 'saas_subscription_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(SaasInvoice::class, 'saas_payment_id');
    }

    public function providerLabel(): string
    {
        return self::PROVIDERS[$this->provider] ?? $this->provider;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
