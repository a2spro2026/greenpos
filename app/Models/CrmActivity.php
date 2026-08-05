<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    protected $table = 'crm_activities';

    public const TYPES = [
        'call' => 'Appel',
        'email' => 'Email',
        'meeting' => 'Réunion',
        'task' => 'Tâche',
        'follow_up' => 'Relance',
        'note' => 'Note',
    ];

    public const TYPE_ICONS = [
        'call' => '📞',
        'email' => '✉️',
        'meeting' => '📅',
        'task' => '✅',
        'follow_up' => '🔁',
        'note' => '📝',
    ];

    public const STATUSES = [
        'planned' => 'Planifiée',
        'done' => 'Terminée',
        'cancelled' => 'Annulée',
    ];

    protected $fillable = [
        'company_id', 'owner_user_id', 'crm_lead_id', 'crm_opportunity_id', 'customer_id',
        'type', 'status', 'subject', 'body',
        'starts_at', 'ends_at', 'due_at', 'completed_at', 'all_day', 'priority', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'all_day' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CrmOpportunity::class, 'crm_opportunity_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
