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
        'other' => 'Autre',
    ];

    protected $fillable = ['sale_id', 'created_by', 'method', 'amount', 'paid_at', 'reference', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'date'];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function methodLabel(): string { return self::METHODS[$this->method] ?? $this->method; }
}
