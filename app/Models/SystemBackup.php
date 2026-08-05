<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemBackup extends Model
{
    protected $fillable = [
        'company_id', 'created_by', 'code', 'type', 'schedule', 'status',
        'disk', 'path', 'size_bytes', 'duration_ms', 'include_files',
        'manifest', 'error_message', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'include_files' => 'boolean',
            'manifest' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'running' => 'En cours',
            'success' => 'Réussie',
            'failed' => 'Échouée',
            default => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
            'failed' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
            'running' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        };
    }

    public function typeLabel(): string
    {
        return $this->type === 'auto' ? 'Automatique' : 'Manuelle';
    }

    public function sizeLabel(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) {
            return $bytes.' o';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' Ko';
        }

        return round($bytes / 1048576, 2).' Mo';
    }

    public function durationLabel(): string
    {
        $ms = (int) $this->duration_ms;
        if ($ms < 1000) {
            return $ms.' ms';
        }

        return round($ms / 1000, 1).' s';
    }
}
