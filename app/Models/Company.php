<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archivée',
    ];

    public const STATUS_COLORS = [
        'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'inactive' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        'archived' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
    ];

    protected $fillable = [
        'name', 'legal_name', 'logo_path', 'activity', 'currency', 'timezone', 'locale',
        'status', 'modules_setup_at', 'archived_at',
        'email', 'phone', 'website', 'address', 'city', 'region', 'postal_code', 'country',
        'ice', 'if_number', 'rc', 'patente', 'tax_id', 'cnss',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'modules_setup_at' => 'datetime',
        ];
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'status', 'is_primary')
            ->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('legal_name', 'like', "%{$term}%")
                ->orWhere('activity', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('country', 'like', "%{$term}%");
        });
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = collect($parts)->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return $letters ?: 'E';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? self::STATUS_COLORS['inactive'];
    }

    public function isOperational(): bool
    {
        return $this->status === 'active';
    }

    public function needsModuleSetup(): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('companies', 'modules_setup_at')) {
            return false;
        }

        return $this->modules_setup_at === null;
    }
}
