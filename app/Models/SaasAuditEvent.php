<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasAuditEvent extends Model
{
    protected $table = 'saas_audit_events';

    public const CATEGORIES = [
        'login' => 'Connexions',
        'error' => 'Erreurs',
        'incident' => 'Incidents',
        'billing' => 'Facturation',
        'tenant' => 'Clients',
        'system' => 'Système',
    ];

    public const SEVERITIES = [
        'info' => 'Info',
        'warning' => 'Avertissement',
        'critical' => 'Critique',
    ];

    protected $fillable = [
        'category', 'severity', 'title', 'body',
        'saas_tenant_id', 'user_id', 'ip_address', 'meta', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(SaasTenant::class, 'saas_tenant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
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
