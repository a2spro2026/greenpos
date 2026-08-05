<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'individual' => 'Particulier',
        'company' => 'Société',
    ];

    public const STATUSES = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ];

    public const STATUS_COLORS = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-slate-100 text-slate-700',
    ];

    public const CATEGORIES = [
        'standard' => 'Standard',
        'vip' => 'VIP',
        'wholesale' => 'Grossiste',
        'retail' => 'Détail',
        'partner' => 'Partenaire',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'created_by',
        'updated_by',
        'code',
        'type',
        'name',
        'company_name',
        'category',
        'status',
        'email',
        'phone',
        'mobile',
        'website',
        'address',
        'city',
        'region',
        'country',
        'postal_code',
        'currency',
        'payment_terms',
        'credit_limit',
        'balance',
        'lifetime_revenue',
        'last_purchase_at',
        'tax_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'lifetime_revenue' => 'decimal:2',
            'last_purchase_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class)->latest();
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(CustomerChangeLog::class)->latest();
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('company_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('city', 'like', $like);
        });
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700';
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ($this->category ?: '—');
    }

    public function displayName(): string
    {
        return $this->type === 'company' && $this->company_name
            ? $this->company_name
            : $this->name;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->displayName())) ?: [];
        $letters = collect($parts)->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return $letters !== '' ? $letters : 'C';
    }
}
