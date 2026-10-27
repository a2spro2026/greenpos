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
        'employee_id',
        'delivery_agent_id',
        'delivery_commission',
        'number',
        'client_uuid',
        'ticket_name',
        'ticket_group',
        'service_mode',
        'service_mode_list_id',
        'predefined_ticket_id',
        'delivery_platform_id',
        'status',
        'service_mode',
        'ticket_name',
        'ticket_discount_percent',
        'delivery_platform',
        'platform_commission',
        'pos_table_id',
        'payment_status_code',
        'ticket_group',
        'service_mode_list_id',
        'predefined_ticket_id',
        'delivery_platform_id',
        'appointment_at',
        'pickup_date',
        'delivery_address',
        'client_uuid',
        'amount_refunded',
        'subtotal_ht',
        'tax_total',
        'discount_total',
        'total_ttc',
        'amount_refunded',
        'currency',
        'notes',
        'appointment_at',
        'pickup_date',
        'delivery_address',
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
            'amount_refunded' => 'decimal:2',
            'appointment_at' => 'datetime',
            'pickup_date' => 'date',
            'held_payload' => 'array',
            'ticket_discount_percent' => 'decimal:2',
            'platform_commission' => 'decimal:2',
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

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(SaleRefund::class)->latest();
    }

    public function serviceModeList(): BelongsTo
    {
        return $this->belongsTo(CustomList::class, 'service_mode_list_id');
    }

    public function deliveryPlatform(): BelongsTo
    {
        return $this->belongsTo(DeliveryPlatform::class);
    }

    public function paymentStatusLabel(): string
    {
        return \App\Services\SalePaymentWorkflowService::STATUSES[$this->payment_status_code] ?? ($this->payment_status_code ?: 'Impayé');
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
