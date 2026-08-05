<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quote extends Model
{
    public const STATUSES = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyé',
        'pending' => 'En attente',
        'accepted' => 'Accepté',
        'refused' => 'Refusé',
        'expired' => 'Expiré',
        'converted' => 'Converti',
    ];

    public const STATUS_COLORS = [
        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        'sent' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        'pending' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-200',
        'accepted' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'refused' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-200',
        'converted' => 'bg-violet-100 text-violet-800 dark:bg-violet-500/20 dark:text-violet-200',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'customer_id',
        'salesperson_id',
        'created_by',
        'updated_by',
        'converted_invoice_id',
        'converted_pos_sale_id',
        'number',
        'status',
        'reference',
        'currency',
        'quoted_at',
        'valid_until',
        'terms',
        'notes',
        'customer_notes',
        'subtotal_ht',
        'tax_total',
        'discount_total',
        'total_ttc',
        'public_token',
        'sent_at',
        'accepted_at',
        'refused_at',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'quoted_at' => 'date',
            'valid_until' => 'date',
            'subtotal_ht' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'refused_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (! $quote->public_token) {
                $quote->public_token = (string) Str::uuid();
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

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function convertedPosSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'converted_pos_sale_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('sort_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(QuoteLog::class)->latest();
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'pending'], true);
    }

    public function isConvertible(): bool
    {
        return in_array($this->status, ['sent', 'pending', 'accepted'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700';
    }

    public function isExpired(): bool
    {
        return $this->valid_until
            && $this->valid_until->isPast()
            && in_array($this->status, ['sent', 'pending'], true);
    }

    public function needsFollowUp(): bool
    {
        return in_array($this->status, ['sent', 'pending'], true)
            && $this->valid_until
            && $this->valid_until->between(now(), now()->addDays(7));
    }

    public function publicUrl(): string
    {
        return route('quotes.public', $this->public_token);
    }

    public function toLinePayload(): array
    {
        return $this->lines->map(fn (QuoteLine $line) => [
            'product_id' => $line->product_id,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'discount_percent' => (float) $line->discount_percent,
            'tax_rate' => (float) $line->tax_rate,
            'description' => $line->description,
        ])->all();
    }
}
