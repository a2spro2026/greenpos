<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'fixed' => 'Fixe',
        'variable' => 'Variable',
    ];

    public const RECURRENCES = [
        'monthly' => 'Mensuelle',
        'quarterly' => 'Trimestrielle',
        'semiannual' => 'Semestrielle',
        'annual' => 'Annuelle',
    ];

    public const CATEGORIES = [
        'rent' => 'Loyer',
        'utilities' => 'Charges',
        'salaries' => 'Salaires',
        'supplies' => 'Fournitures',
        'transport' => 'Transport',
        'marketing' => 'Marketing',
        'maintenance' => 'Maintenance',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'supplier_id',
        'created_by',
        'label',
        'category',
        'expense_type',
        'amount',
        'currency',
        'expense_date',
        'payment_method',
        'is_recurring',
        'recurrence',
        'next_due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'next_due_date' => 'date',
            'is_recurring' => 'boolean',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->expense_type] ?? $this->expense_type;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ($this->category ?: '—');
    }

    public function recurrenceLabel(): string
    {
        return self::RECURRENCES[$this->recurrence] ?? ($this->recurrence ?: '—');
    }
}
