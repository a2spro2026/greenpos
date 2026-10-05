<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    public const METHODS = [
        'cash' => 'Espèces',
        'card' => 'Carte bancaire',
        'bank_transfer' => 'Virement',
        'mobile' => 'Paiement mobile',
        'check' => 'Chèque',
        'credit' => 'Crédit',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'sale_id', 'created_by', 'method', 'amount', 'paid_at', 'reference', 'notes',
        'custom_list_id', 'is_deferred', 'transfer_mode', 'transaction_number', 'piece_number',
        'bank_name', 'issue_date', 'due_date', 'confirmed_at', 'collection_status',
        'received_amount', 'change_amount', 'scheduled_for',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'is_deferred' => 'boolean',
            'issue_date' => 'date',
            'due_date' => 'date',
            'confirmed_at' => 'datetime',
            'received_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'scheduled_for' => 'datetime',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function customList(): BelongsTo { return $this->belongsTo(CustomList::class); }
    public function collections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentCollection::class)->latest();
    }

    public function methodLabel(): string { return self::METHODS[$this->method] ?? $this->method; }
}
