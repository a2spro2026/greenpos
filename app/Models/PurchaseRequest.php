<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    public const STATUSES = [
        'draft' => 'Brouillon',
        'submitted' => 'Soumise',
        'approved' => 'Approuvée',
        'rejected' => 'Refusée',
        'converted' => 'Convertie',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'requested_by',
        'number',
        'status',
        'title',
        'notes',
        'converted_order_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
