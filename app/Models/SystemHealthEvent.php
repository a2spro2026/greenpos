<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemHealthEvent extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'category', 'severity', 'title', 'body', 'payload',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'backup' => 'Sauvegarde',
            'restore' => 'Restauration',
            'error' => 'Erreur',
            'incident' => 'Incident',
            'health' => 'Santé',
            default => $this->category,
        };
    }
}
