<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'physical' => 'Produit physique',
        'service' => 'Service',
        'variant_parent' => 'Produit à variantes',
        'pack' => 'Pack / lot',
        'raw_material' => 'Matière première',
        'digital' => 'Numérique',
    ];

    public const STATUSES = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'archived' => 'Archivé',
    ];

    public const UNITS = [
        'pce' => 'Pièce',
        'kg' => 'Kilogramme',
        'g' => 'Gramme',
        'L' => 'Litre',
        'm' => 'Mètre',
        'm2' => 'Mètre carré',
        'h' => 'Heure',
        'colis' => 'Colis',
    ];

    protected $fillable = [
        'company_id',
        'category_id',
        'brand_id',
        'supplier_id',
        'created_by',
        'updated_by',
        'type',
        'name',
        'slug',
        'sku',
        'barcode',
        'qr_code',
        'unit',
        'short_description',
        'description',
        'purchase_price',
        'sale_price',
        'tax_rate',
        'discount_type',
        'discount_value',
        'discount_starts_at',
        'discount_ends_at',
        'status',
        'track_stock',
        'image_path',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_starts_at' => 'datetime',
            'discount_ends_at' => 'datetime',
            'track_stock' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(ProductChangeLog::class)->latest();
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
            ->withPivot(['is_available', 'sale_price_override'])
            ->withTimestamps();
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! filled($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('barcode', 'like', $like)
                ->orWhere('short_description', 'like', $like);
        });
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function effectiveSalePrice(): float
    {
        $price = (float) $this->sale_price;

        if ($this->discount_type && $this->discount_value !== null) {
            $now = now();
            $inPeriod = (! $this->discount_starts_at || $now->gte($this->discount_starts_at))
                && (! $this->discount_ends_at || $now->lte($this->discount_ends_at));

            if ($inPeriod) {
                if ($this->discount_type === 'percent') {
                    $price -= $price * ((float) $this->discount_value / 100);
                } elseif ($this->discount_type === 'amount') {
                    $price -= (float) $this->discount_value;
                }
            }
        }

        return max(0, round($price, 2));
    }
}
