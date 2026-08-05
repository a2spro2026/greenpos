<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    public const STATUSES = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyée',
        'confirmed' => 'Confirmée',
        'partial' => 'Partiellement reçue',
        'received' => 'Reçue',
        'cancelled' => 'Annulée',
    ];

    public const STATUS_COLORS = [
        'draft' => 'bg-slate-100 text-slate-700',
        'sent' => 'bg-sky-100 text-sky-800',
        'confirmed' => 'bg-indigo-100 text-indigo-800',
        'partial' => 'bg-amber-100 text-amber-800',
        'received' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-rose-100 text-rose-800',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'supplier_id',
        'created_by',
        'updated_by',
        'number',
        'reference',
        'status',
        'currency',
        'ordered_at',
        'expected_at',
        'notes',
        'subtotal_ht',
        'tax_total',
        'discount_total',
        'total_ttc',
        'sent_at',
        'confirmed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'date',
            'expected_at' => 'date',
            'subtotal_ht' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('sort_order');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PurchaseOrderLog::class)->latest();
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft'], true);
    }

    public function canReceive(): bool
    {
        return in_array($this->status, ['sent', 'confirmed', 'partial'], true);
    }

    public function remainingQuantity(): float
    {
        return (float) $this->lines->sum(fn (PurchaseOrderLine $line) => max(0, (float) $line->quantity - (float) $line->received_quantity));
    }
}
