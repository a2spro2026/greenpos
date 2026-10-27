<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const ORIGINS = [
        'manual' => 'Manuel',
        'pos' => 'POS',
        'quote' => 'Devis',
        'ecommerce' => 'E-commerce',
    ];

    public const STATUSES = [
        'draft' => 'Brouillon',
        'confirmed' => 'Confirmée',
        'preparing' => 'En préparation',
        'delivered' => 'Livrée',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        'returned' => 'Retournée',
    ];

    public const STATUS_COLORS = [
        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        'confirmed' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        'preparing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-200',
        'delivered' => 'bg-teal-100 text-teal-800 dark:bg-teal-500/20 dark:text-teal-200',
        'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'returned' => 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-200',
    ];

    public const SERVICE_MODES = [
        'dine_in' => 'Sur place',
        'takeaway' => 'À emporter',
        'delivery' => 'Livraison',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'customer_id', 'salesperson_id',
        'employee_id', 'delivery_agent_id',
        'created_by', 'updated_by', 'pos_sale_id', 'quote_id', 'invoice_id',
        'number', 'origin', 'service_mode', 'status', 'reference', 'currency',
        'sold_at', 'notes', 'delivery_address', 'ticket_name', 'delivery_commission',
        'expected_delivery_at', 'stock_state', 'contact_phone',
        'subtotal_ht', 'tax_total', 'discount_total', 'total_ttc',
        'amount_paid', 'amount_returned',
        'confirmed_at', 'delivered_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'date',
            'expected_delivery_at' => 'date',
            'subtotal_ht' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_returned' => 'decimal:2',
            'delivery_commission' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function salesperson(): BelongsTo { return $this->belongsTo(User::class, 'salesperson_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function deliveryAgent(): BelongsTo { return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id'); }
    public function posSale(): BelongsTo { return $this->belongsTo(PosSale::class, 'pos_sale_id'); }
    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }

    public function lines(): HasMany { return $this->hasMany(SaleLine::class)->orderBy('sort_order'); }
    public function payments(): HasMany { return $this->hasMany(SalePayment::class)->orderByDesc('paid_at'); }
    public function returns(): HasMany { return $this->hasMany(SaleReturn::class)->latest(); }
    public function logs(): HasMany { return $this->hasMany(SaleLog::class)->latest(); }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function isEditable(): bool { return $this->status === 'draft'; }

    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function statusColor(): string { return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700'; }
    public function originLabel(): string { return self::ORIGINS[$this->origin] ?? $this->origin; }
    public function serviceModeLabel(): string { return self::SERVICE_MODES[$this->service_mode] ?? ($this->service_mode ?: '—'); }

    public function stockStateLabel(): ?string
    {
        return match ($this->stock_state) {
            'reserved' => 'Sortie en cours',
            'out' => 'Sorti (livré)',
            default => null,
        };
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total_ttc - (float) $this->amount_paid - (float) $this->amount_returned, 2));
    }
}
