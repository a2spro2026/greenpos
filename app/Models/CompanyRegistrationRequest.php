<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyRegistrationRequest extends Model
{
    public const STATUS_PENDING = 'EN_ATTENTE';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_REJECTED = 'REFUSEE';

    public const STATUS_SUSPENDED = 'SUSPENDUE';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_REJECTED => 'Refusée',
        self::STATUS_SUSPENDED => 'Suspendue',
    ];

    protected $fillable = [
        'reference', 'owner_name', 'owner_phone', 'owner_email', 'password_hash',
        'company_name', 'activity', 'country', 'city', 'address', 'currency',
        'store_name', 'saas_plan_id', 'status', 'rejection_reason', 'suspend_reason',
        'company_id', 'reviewed_by', 'reviewed_at', 'approved_at', 'rejected_at',
        'suspended_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canApprove(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SUSPENDED], true)
            && ! $this->company_id;
    }

    public function canReject(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canSuspend(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE], true);
    }
}
