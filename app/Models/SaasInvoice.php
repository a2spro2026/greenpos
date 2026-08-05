<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasInvoice extends Model
{
    protected $table = 'saas_invoices';

    public const STATUSES = [
        'draft' => 'Brouillon',
        'issued' => 'Émise',
        'paid' => 'Payée',
        'void' => 'Annulée',
    ];

    protected $fillable = [
        'saas_tenant_id', 'saas_payment_id', 'saas_subscription_id', 'number',
        'status', 'subtotal', 'tax', 'total', 'currency', 'issued_on', 'due_on',
        'paid_at', 'line_items', 'pdf_path', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_on' => 'date',
            'due_on' => 'date',
            'paid_at' => 'datetime',
            'line_items' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(SaasTenant::class, 'saas_tenant_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SaasPayment::class, 'saas_payment_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SaasSubscription::class, 'saas_subscription_id');
    }

    public const STATUS_COLORS = [
        'draft' => 'bg-slate-500/15 text-slate-300',
        'issued' => 'bg-sky-500/15 text-sky-300',
        'paid' => 'bg-emerald-500/15 text-emerald-300',
        'void' => 'bg-rose-500/15 text-rose-300',
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-500/15 text-slate-300';
    }
}
