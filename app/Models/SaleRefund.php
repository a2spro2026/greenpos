<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRefund extends Model
{
    public const TYPES = [
        'partial' => 'Partiel',
        'full' => 'Total',
    ];

    public const STATUSES = [
        'completed' => 'Remboursé',
        'restocked' => 'Remboursé et réintégré',
    ];

    protected $fillable = [
        'company_id', 'sale_id', 'pos_sale_id', 'created_by', 'number', 'type',
        'method', 'reason', 'notes', 'amount', 'restock', 'status', 'lines', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'restock' => 'boolean',
            'lines' => 'array',
            'refunded_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
