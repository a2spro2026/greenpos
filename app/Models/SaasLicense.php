<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SaasLicense extends Model
{
    protected $table = 'saas_licenses';

    public const STATUSES = [
        'active' => 'Active',
        'revoked' => 'Révoquée',
        'expired' => 'Expirée',
    ];

    protected $fillable = [
        'saas_tenant_id', 'saas_subscription_id', 'license_key', 'status',
        'max_activations', 'activations_count', 'issued_at', 'expires_at', 'revoked_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SaasLicense $license) {
            if (! $license->license_key) {
                $license->license_key = 'GP-'.strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
            }
            if (! $license->issued_at) {
                $license->issued_at = now();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(SaasTenant::class, 'saas_tenant_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SaasSubscription::class, 'saas_subscription_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
