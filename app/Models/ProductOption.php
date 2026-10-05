<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOption extends Model
{
    public const SELECTION_MODES = [
        'fixed' => 'Choix unique',
        'multiple' => 'Choix multiples',
    ];

    protected $fillable = [
        'company_id', 'name', 'selection_mode', 'is_required', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(OptionVariant::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_option_product')->withTimestamps();
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function selectionLabel(): string
    {
        return self::SELECTION_MODES[$this->selection_mode] ?? $this->selection_mode;
    }
}
