<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierReturn extends Model
{
    public const STATUSES = [
        'draft' => 'Brouillon',
        'validated' => 'Validé — stock sorti',
        'credited' => 'Avoir reçu',
        'cancelled' => 'Annulé',
    ];

    public const REASONS = ['Produit défectueux', 'Erreur de livraison', 'Périmé / DLC courte', 'Surplus', 'Autre'];

    protected $fillable = [
        'company_id', 'store_id', 'supplier_id', 'purchase_order_id', 'purchase_receipt_id', 'number', 'status',
        'reason', 'notes', 'returned_at', 'total_ht', 'total_tax', 'total_ttc', 'credit_note_number',
        'credit_note_amount', 'credited_at', 'validated_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'date', 'credited_at' => 'date', 'validated_at' => 'datetime',
            'total_ht' => 'decimal:2', 'total_tax' => 'decimal:2', 'total_ttc' => 'decimal:2', 'credit_note_amount' => 'decimal:2',
        ];
    }

    public function lines(): HasMany { return $this->hasMany(SupplierReturnLine::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function order(): BelongsTo { return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id'); }
    public function receipt(): BelongsTo { return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }

    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }
}
