<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPayment extends Model
{
    public const METHODS = [
        'cash' => 'Espèces',
        'card' => 'Carte bancaire',
        'mobile' => 'Paiement mobile',
        'bank_transfer' => 'Virement',
        'check' => 'Chèque',
        'credit' => 'Crédit',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'pos_sale_id',
        'method',
        'amount',
        'tendered',
        'change_amount',
        'reference',
        'custom_list_id',
        'is_deferred',
        'transfer_mode',
        'transaction_number',
        'piece_number',
        'bank_name',
        'issue_date',
        'due_date',
        'confirmed_at',
        'collection_status',
        'received_amount',
        'scheduled_for',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tendered' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'is_deferred' => 'boolean',
            'issue_date' => 'date',
            'due_date' => 'date',
            'confirmed_at' => 'datetime',
            'received_amount' => 'decimal:2',
            'scheduled_for' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function customList(): BelongsTo
    {
        return $this->belongsTo(CustomList::class);
    }

    public function collections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentCollection::class)->latest();
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
