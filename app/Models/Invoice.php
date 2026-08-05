<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    public const TYPES = [
        'invoice' => 'Facture',
        'credit_note' => 'Avoir',
    ];

    public const STATUSES = [
        'draft' => 'Brouillon',
        'pending' => 'En attente',
        'partial' => 'Partiellement payée',
        'paid' => 'Payée',
        'cancelled' => 'Annulée',
        'expired' => 'Expirée',
    ];

    public const STATUS_COLORS = [
        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        'pending' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        'partial' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
        'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-200',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'customer_id',
        'created_by',
        'updated_by',
        'pos_sale_id',
        'parent_invoice_id',
        'type',
        'number',
        'status',
        'reference',
        'currency',
        'invoiced_at',
        'due_at',
        'payment_terms',
        'notes',
        'customer_notes',
        'subtotal_ht',
        'tax_total',
        'discount_total',
        'total_ttc',
        'amount_paid',
        'balance_due',
        'public_token',
        'sent_at',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'invoiced_at' => 'date',
            'due_at' => 'date',
            'subtotal_ht' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (! $invoice->public_token) {
                $invoice->public_token = (string) Str::uuid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(self::class, 'parent_invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderByDesc('paid_at');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InvoiceLog::class)->latest();
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeInvoices(Builder $query): Builder
    {
        return $query->where('type', 'invoice');
    }

    public function scopeCreditNotes(Builder $query): Builder
    {
        return $query->where('type', 'credit_note');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'partial', 'expired']);
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft' && $this->type === 'invoice';
    }

    public function isCreditNote(): bool
    {
        return $this->type === 'credit_note';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isOverdue(): bool
    {
        return $this->due_at
            && $this->due_at->isPast()
            && in_array($this->status, ['pending', 'partial', 'expired'], true)
            && (float) $this->balance_due > 0;
    }

    public function publicUrl(): string
    {
        return route('invoices.public', $this->public_token);
    }
}
