<?php

namespace App\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SaasTenant extends Model
{
    use SoftDeletes;

    protected $table = 'saas_tenants';

    public const STATUSES = [
        'trial' => 'Essai',
        'active' => 'Actif',
        'suspended' => 'Suspendu',
        'cancelled' => 'Annulé',
        'archived' => 'Archivé',
    ];

    public const STATUS_COLORS = [
        'trial' => 'bg-sky-500/15 text-sky-300',
        'active' => 'bg-emerald-500/15 text-emerald-300',
        'suspended' => 'bg-amber-500/15 text-amber-300',
        'cancelled' => 'bg-rose-500/15 text-rose-300',
        'archived' => 'bg-slate-500/15 text-slate-300',
    ];

    protected $fillable = [
        'company_id', 'name', 'slug', 'legal_name', 'email', 'phone',
        'country', 'city', 'logo_path', 'primary_domain', 'storage_used_mb',
        'status', 'trial_ends_at', 'suspended_at', 'suspend_reason', 'archived_at',
        'owner_user_id', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SaasTenant $tenant) {
            if (! $tenant->slug) {
                $tenant->slug = Str::slug($tenant->name).'-'.Str::lower(Str::random(4));
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SaasSubscription::class, 'saas_tenant_id');
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(SaasSubscription::class, 'saas_tenant_id')
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->latestOfMany();
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(SaasLicense::class, 'saas_tenant_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SaasPayment::class, 'saas_tenant_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SaasInvoice::class, 'saas_tenant_id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(SaasDomain::class, 'saas_tenant_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%")
                ->orWhere('legal_name', 'like', "%{$term}%");
        });
    }

    public function statusLabel(): string
    {
        if ($this->archived_at) {
            return self::STATUSES['archived'];
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        if ($this->archived_at) {
            return self::STATUS_COLORS['archived'];
        }

        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-500/15 text-slate-300';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function usersCount(): int
    {
        if (! $this->company_id) {
            return 0;
        }

        return User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $this->company_id))
            ->count();
    }

    public function storesCount(): int
    {
        if (! $this->company_id || ! class_exists(Store::class)) {
            return 0;
        }

        return Store::query()->where('company_id', $this->company_id)->count();
    }

    public function storageLabel(): string
    {
        $used = (int) ($this->storage_used_mb ?? 0);
        $max = (int) ($this->currentSubscription?->plan?->storage_gb ?? 0) * 1024;

        if ($max > 0) {
            return number_format($used).' / '.number_format($max).' Mo';
        }

        return number_format($used).' Mo';
    }

    public function domainLabel(): string
    {
        return $this->primary_domain
            ?: ($this->domains()->where('is_primary', true)->value('domain')
                ?: ($this->domains()->value('domain') ?: '—'));
    }
}
