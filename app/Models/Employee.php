<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    public const JOB_ROLES = [
        'admin' => 'Administrateur',
        'manager' => 'Manager',
        'cashier' => 'Caissier',
        'vendor' => 'Vendeur',
        'other' => 'Autre',
    ];

    public const STATUSES = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'suspended' => 'Suspendu',
    ];

    public const STATUS_COLORS = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-slate-100 text-slate-700',
        'suspended' => 'bg-amber-100 text-amber-800',
    ];

    public const PAY_TYPES = [
        'hourly' => 'Horaire',
        'daily' => 'Journalier',
        'weekly' => 'Hebdomadaire',
        'monthly' => 'Mensuel',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'user_id',
        'code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'job_role',
        'status',
        'hire_date',
        'photo_path',
        'bank_iban',
        'bank_name',
        'commission_percent',
        'commission_fixed',
        'pay_type',
        'base_rate',
        'overtime_multiplier',
        'normal_hours_per_day',
        'rest_day',
        'absence_penalty',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'commission_percent' => 'decimal:2',
            'commission_fixed' => 'decimal:2',
            'base_rate' => 'decimal:2',
            'overtime_multiplier' => 'decimal:2',
            'normal_hours_per_day' => 'decimal:2',
            'absence_penalty' => 'decimal:2',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
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
            $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", [$like]);
        });
    }

    public function jobRoleLabel(): string
    {
        return self::JOB_ROLES[$this->job_role] ?? $this->job_role;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700';
    }

    public function payTypeLabel(): string
    {
        return self::PAY_TYPES[$this->pay_type] ?? $this->pay_type;
    }

    public function initials(): string
    {
        $letters = collect([$this->first_name, $this->last_name])
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : 'E';
    }
}
