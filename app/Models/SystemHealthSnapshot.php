<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemHealthSnapshot extends Model
{
    protected $fillable = [
        'company_id', 'overall', 'database_status', 'response_ms',
        'disk_used_percent', 'disk_free_bytes', 'services', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'disk_used_percent' => 'decimal:2',
            'services' => 'array',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function overallLabel(): string
    {
        return match ($this->overall) {
            'healthy' => 'Sain',
            'degraded' => 'Dégradé',
            'critical' => 'Critique',
            default => $this->overall,
        };
    }
}
