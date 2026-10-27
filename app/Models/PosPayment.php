<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPayment extends Model
{
    public const METHODS = [
        'cash' => 'Espèces',
        'card' => 'Carte bancaire',
        'instant_transfer' => 'Virement instantané',
        'transfer' => 'Virement simple',
        'cheque' => 'Chèque',
        'effet' => 'Effet',
        'mobile' => 'Paiement mobile',
        'credit' => 'Crédit client',
    ];

    /** Modes nécessitant un numéro et une date d'échéance. */
    public const INSTRUMENT_METHODS = ['cheque', 'effet'];

    protected $fillable = [
        'pos_sale_id',
        'method',
        'amount',
        'tendered',
        'change_amount',
        'reference',
        'due_date',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tendered' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'due_date' => 'date',
            'collected_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
