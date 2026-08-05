<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CrmLead extends Model
{
    use SoftDeletes;

    protected $table = 'crm_leads';

    public const TYPES = [
        'prospect' => 'Prospect',
        'lead' => 'Lead',
    ];

    public const STATUSES = [
        'new' => 'Nouveau',
        'contacted' => 'Contacté',
        'qualified' => 'Qualifié',
        'unqualified' => 'Non qualifié',
        'converted' => 'Converti',
        'archived' => 'Archivé',
    ];

    public const STATUS_COLORS = [
        'new' => 'bg-sky-100 text-sky-800',
        'contacted' => 'bg-indigo-100 text-indigo-800',
        'qualified' => 'bg-emerald-100 text-emerald-800',
        'unqualified' => 'bg-amber-100 text-amber-800',
        'converted' => 'bg-teal-100 text-teal-800',
        'archived' => 'bg-slate-100 text-slate-700',
    ];

    public const SOURCES = [
        'website' => 'Site web',
        'referral' => 'Recommandation',
        'cold_call' => 'Appel froid',
        'email' => 'Email',
        'event' => 'Événement',
        'partner' => 'Partenaire',
        'other' => 'Autre',
    ];

    public const RATINGS = [
        'hot' => 'Chaud',
        'warm' => 'Tiède',
        'cold' => 'Froid',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'owner_user_id', 'customer_id', 'number',
        'type', 'status', 'source', 'rating', 'score',
        'company_name', 'first_name', 'last_name', 'email', 'phone', 'mobile',
        'job_title', 'city', 'country', 'website',
        'estimated_value', 'currency', 'description', 'tags', 'meta',
        'qualified_at', 'converted_at', 'archived_at', 'last_contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'tags' => 'array',
            'meta' => 'array',
            'qualified_at' => 'datetime',
            'converted_at' => 'datetime',
            'archived_at' => 'datetime',
            'last_contacted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CrmLead $lead) {
            if (! $lead->number) {
                $lead->number = 'LD-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class, 'crm_lead_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'crm_lead_id');
    }

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(function (Builder $inner) use ($term) {
            $inner->where('company_name', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('number', 'like', "%{$term}%");
        });
    }

    public function displayName(): string
    {
        $person = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $this->company_name ?: ($person !== '' ? $person : ($this->email ?: $this->number));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? ($this->source ?: '—');
    }

    public function ratingLabel(): string
    {
        return self::RATINGS[$this->rating] ?? ($this->rating ?: '—');
    }
}
