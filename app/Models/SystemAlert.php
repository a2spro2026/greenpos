<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemAlert extends Model
{
    protected $fillable = [
        'company_id', 'type', 'severity', 'title', 'body',
        'is_resolved', 'resolved_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    public function severityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
            default => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'disk_low' => 'Espace disque',
            'backup_failed' => 'Sauvegarde échouée',
            'service_down' => 'Service indisponible',
            'database_down' => 'Base inaccessible',
            default => $this->type,
        };
    }
}
