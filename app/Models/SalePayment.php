<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    public const METHODS = [
        'cash' => 'Espèces',
        'card' => 'Carte bancaire',
        'instant_transfer' => 'Virement instantané',
        'bank_transfer' => 'Virement simple',
        'mobile' => 'Paiement mobile',
        'check' => 'Chèque',
        'effet' => 'Effet',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'sale_id', 'created_by', 'method', 'amount', 'paid_at', 'reference', 'notes',
        'status', 'is_deferred', 'due_at', 'collected_at', 'collection_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'due_at' => 'datetime',
            'collected_at' => 'datetime',
            'is_deferred' => 'boolean',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function methodLabel(): string { return self::METHODS[$this->method] ?? $this->method; }
}
