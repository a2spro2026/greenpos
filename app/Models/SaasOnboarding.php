<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasOnboarding extends Model
{
    protected $table = 'saas_onboardings';

    public const STATUSES = [
        'registered' => 'Inscription',
        'provisioned' => 'Espace créé',
        'wizard' => 'Configuration',
        'completed' => 'Terminé',
    ];

    protected $fillable = [
        'user_id',
        'company_id',
        'saas_tenant_id',
        'saas_plan_id',
        'status',
        'wizard_step',
        'draft',
        'checklist',
        'welcome_shown',
        'provisioned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'draft' => 'array',
            'checklist' => 'array',
            'welcome_shown' => 'boolean',
            'provisioned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(SaasTenant::class, 'saas_tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function needsWizard(): bool
    {
        return in_array($this->status, ['provisioned', 'wizard'], true);
    }
}
