<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentCollection extends Model
{
    public const ACTIONS = [
        'scheduled' => 'Planifié',
        'rescheduled' => 'Replanifié',
        'collected' => 'Encaissé',
        'cancelled' => 'Annulé',
        'confirmed' => 'Confirmé',
    ];

    protected $fillable = [
        'company_id', 'sale_payment_id', 'pos_payment_id', 'created_by',
        'action', 'amount', 'scheduled_for', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'scheduled_for' => 'datetime',
        ];
    }

    public function salePayment(): BelongsTo
    {
        return $this->belongsTo(SalePayment::class);
    }

    public function posPayment(): BelongsTo
    {
        return $this->belongsTo(PosPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
