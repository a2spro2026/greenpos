<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    public const STATUSES = [
        'pending' => 'En attente',
        'paid' => 'Payé',
        'partial' => 'Partiel',
    ];

    public const STATUS_COLORS = [
        'pending' => 'bg-amber-100 text-amber-800',
        'paid' => 'bg-emerald-100 text-emerald-800',
        'partial' => 'bg-sky-100 text-sky-800',
    ];

    public const PAY_TYPES = [
        'hourly' => 'Horaire',
        'daily' => 'Journalier',
        'weekly' => 'Hebdomadaire',
        'monthly' => 'Mensuel',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Espèces',
        'transfer' => 'Virement',
        'check' => 'Chèque',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'employee_id',
        'year',
        'month',
        'pay_type',
        'base_rate',
        'normal_hours',
        'overtime_hours',
        'worked_days',
        'absent_days',
        'gross',
        'overtime_amount',
        'absence_deduction',
        'prime',
        'bonus',
        'advance',
        'retention',
        'net',
        'status',
        'payment_method',
        'paid_amount',
        'paid_at',
        'notes',
        'calculated_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'base_rate' => 'decimal:2',
            'normal_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'worked_days' => 'integer',
            'absent_days' => 'integer',
            'gross' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'prime' => 'decimal:2',
            'bonus' => 'decimal:2',
            'advance' => 'decimal:2',
            'retention' => 'decimal:2',
            'net' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'calculated_at' => 'datetime',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
            $q->where('notes', 'like', $like)
                ->orWhere('status', 'like', $like)
                ->orWhereHas('employee', function (Builder $eq) use ($like) {
                    $eq->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                });
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

    public function payTypeLabel(): string
    {
        return self::PAY_TYPES[$this->pay_type] ?? $this->pay_type;
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ($this->payment_method ?: '—');
    }

    public function periodLabel(): string
    {
        return sprintf('%02d/%d', $this->month, $this->year);
    }
}
