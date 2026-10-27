<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmIncident extends Model
{
    public const PRIORITIES = [
        'low' => 'Basse',
        'medium' => 'Moyenne',
        'urgent' => 'Urgente',
    ];

    public const STATUSES = [
        'open' => 'Ouvert',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu',
        'cancelled' => 'Annulé',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'employee_id', 'type', 'priority', 'status',
        'title', 'description', 'assigned_to', 'auto_assigned', 'resolved_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'auto_assigned' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
