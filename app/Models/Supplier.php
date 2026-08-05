<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'risk' => 'À risque',
    ];

    public const STATUS_COLORS = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-slate-100 text-slate-700',
        'risk' => 'bg-amber-100 text-amber-800',
    ];

    public const CATEGORIES = [
        'general' => 'Général',
        'food' => 'Alimentaire',
        'non_food' => 'Non alimentaire',
        'services' => 'Services',
        'logistics' => 'Logistique',
    ];

    protected $fillable = [
        'company_id',
        'code',
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
        'delivery_delay_days',
        'tax_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_delay_days' => 'integer',
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

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class)->latest();
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(SupplierChangeLog::class)->latest();
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

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = collect($parts)->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return $letters !== '' ? $letters : 'F';
    }
}
