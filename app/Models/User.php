<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const STATUSES = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'invited' => 'Invité',
    ];

    public const STATUS_COLORS = [
        'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'inactive' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        'invited' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
    ];

    public const ROLES = [
        'super_admin' => 'Super Administrateur',
        'owner' => 'Propriétaire',
        'admin' => 'Administrateur',
        'manager' => 'Manager',
        'accountant' => 'Comptable',
        'sales' => 'Commercial',
        'cashier' => 'Caissier',
        'storekeeper' => 'Magasinier',
        'employee' => 'Employé',
    ];

    public const DEPARTMENTS = [
        'direction' => 'Direction',
        'vente' => 'Vente',
        'caisse' => 'Caisse',
        'stock' => 'Stock',
        'achat' => 'Achat',
        'comptable' => 'Comptabilité',
        'support' => 'Support',
    ];

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'is_platform_admin',
        'username',
        'phone',
        'photo_path',
        'job_title',
        'department',
        'hired_at',
        'status',
        'password',
        'last_login_at',
        'last_login_ip',
        'last_login_device',
        'invited_at',
        'deactivated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'hired_at' => 'date',
            'last_login_at' => 'datetime',
            'invited_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('role', 'status', 'is_primary')
            ->withTimestamps();
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)->withTimestamps();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UserLog::class)->latest();
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(UserLoginLog::class)->latest('logged_in_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class)->latest();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    public function roleIn(?Company $company): ?string
    {
        if (! $company) {
            return null;
        }

        return $this->companies()
            ->where('companies.id', $company->id)
            ->first()
            ?->pivot
            ?->role;
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! filled($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('username', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('job_title', 'like', $like);
        });
    }

    public function displayName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : ($this->name ?: $this->email);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', $this->displayName()) ?: [];
        $initials = collect($parts)->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return $initials !== '' ? $initials : '?';
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700';
    }

    public function roleLabel(?Company $company = null): string
    {
        $role = $this->roleIn($company) ?? '—';

        return self::ROLES[$role] ?? $role;
    }

    public function departmentLabel(): string
    {
        return self::DEPARTMENTS[$this->department] ?? ($this->department ?: '—');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
