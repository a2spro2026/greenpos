<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTable extends Model
{
    public const STATUSES = [
        'free' => 'Libre',
        'occupied' => 'Occupée',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'name', 'group_name', 'seats',
        'sort_order', 'status', 'current_pos_sale_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function currentSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'current_pos_sale_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
