<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashJournalEntry extends Model
{
    public const DIRECTIONS = [
        'in' => 'Entrée',
        'out' => 'Sortie',
    ];

    public const SOURCE_TYPES = [
        'sale' => 'Vente',
        'expense' => 'Dépense',
        'collection' => 'Encaissement',
        'manual' => 'Manuel',
        'payroll' => 'Paie',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'entry_date',
        'direction',
        'source_type',
        'source_id',
        'label',
        'amount',
        'payment_method',
        'balance_after',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
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

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function directionLabel(): string
    {
        return self::DIRECTIONS[$this->direction] ?? $this->direction;
    }

    public function sourceTypeLabel(): string
    {
        return self::SOURCE_TYPES[$this->source_type] ?? $this->source_type;
    }

    public function signedAmount(): float
    {
        return $this->direction === 'in'
            ? abs((float) $this->amount)
            : -abs((float) $this->amount);
    }
}
