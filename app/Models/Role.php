<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const COLOR_CLASSES = [
        'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'teal' => 'bg-teal-100 text-teal-800 dark:bg-teal-500/20 dark:text-teal-200',
        'sky' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
        'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-200',
        'violet' => 'bg-violet-100 text-violet-800 dark:bg-violet-500/20 dark:text-violet-200',
        'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-200',
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
    ];

    protected $fillable = [
        'company_id', 'slug', 'name', 'description', 'color',
        'is_system', 'is_super', 'is_default', 'users_count_cache',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_super' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RoleLog::class)->latest();
    }

    public function scopeForCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->where(function (Builder $q) use ($companyId) {
            $q->whereNull('company_id');
            if ($companyId) {
                $q->orWhere('company_id', $companyId);
            }
        });
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true)->whereNull('company_id');
    }

    public function colorClass(): string
    {
        return self::COLOR_CLASSES[$this->color ?? 'slate'] ?? self::COLOR_CLASSES['slate'];
    }

    public function isEditable(): bool
    {
        // System template roles can have permissions edited at company level via clone;
        // company roles are fully editable; system roles permissions are editable by owner for customization
        return ! $this->is_super;
    }

    public function isDeletable(): bool
    {
        return ! $this->is_system && ! $this->is_default;
    }

    public function hasPermission(string $key): bool
    {
        if ($this->is_super) {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('key', $key);
        }

        return $this->permissions()->where('key', $key)->exists();
    }

    public function permissionKeys(): array
    {
        if ($this->relationLoaded('permissions')) {
            return $this->permissions->pluck('key')->all();
        }

        return $this->permissions()->pluck('key')->all();
    }
}
