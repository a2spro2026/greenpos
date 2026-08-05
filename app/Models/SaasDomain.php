<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasDomain extends Model
{
    protected $table = 'saas_domains';

    public const STATUSES = [
        'pending' => 'En attente',
        'active' => 'Actif',
        'failed' => 'Échec',
    ];

    protected $fillable = [
        'saas_tenant_id', 'domain', 'is_primary', 'status',
        'ssl_enabled', 'verified_at', 'verification_token', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'ssl_enabled' => 'boolean',
            'verified_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(SaasTenant::class, 'saas_tenant_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
