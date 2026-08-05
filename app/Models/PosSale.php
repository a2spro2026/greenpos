<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    public const STATUSES = [
        'held' => 'Suspendue',
        'completed' => 'Validée',
        'cancelled' => 'Annulée',
    ];

    public const STATUS_COLORS = [
        'held' => 'bg-amber-100 text-amber-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-rose-100 text-rose-800',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'pos_session_id',
        'customer_id',
        'cashier_id',
        'number',
        'status',
        'subtotal_ht',
        'tax_total',
        'discount_total',
        'total_ttc',
        'currency',
        'notes',
        'held_payload',
        'held_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_ht' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'held_payload' => 'array',
            'held_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
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
}
