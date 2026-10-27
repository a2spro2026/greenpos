<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomList extends Model
{
    public const TYPES = [
        'tickets_predefinis' => 'Tickets prédéfinis',
        'mode_de_service' => 'Modes de service',
        'mode_de_paiement' => 'Modes de paiement',
        'taxes' => 'Taxes',
        'remises' => 'Remises',
        'depenses' => 'Catégories de dépenses',
    ];

    public const OPERATIONAL_MODES = [
        'dine_in' => 'Sur place',
        'takeaway' => 'À emporter',
        'delivery' => 'Livraison',
        'other' => 'Autre',
    ];

    public const PAYMENT_TIMINGS = [
        'immediate' => 'Immédiat',
        'deferred' => 'Différé',
    ];

    public const PAYMENT_FIELDS = [
        'transfer_mode' => 'Mode de virement',
        'transaction_number' => 'N° transaction',
        'piece_number' => 'N° de pièce',
        'bank_name' => 'Banque',
        'issue_date' => 'Date d\'émission',
        'due_date' => 'Date d\'échéance',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'type', 'name', 'code', 'sort_order',
        'is_active', 'is_default', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'metadata' => 'array',
            'sort_order' => 'integer',
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

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata ?? [], $key, $default);
    }
}
