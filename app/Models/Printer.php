<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Printer extends Model
{
    public const ROLES = [
        'customer' => 'Client',
        'kitchen' => 'Cuisine',
        'both' => 'Client et cuisine',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'name', 'role', 'is_default', 'is_active',
        'ticket_config', 'kitchen_config', 'advanced_config',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'ticket_config' => 'array',
            'kitchen_config' => 'array',
            'advanced_config' => 'array',
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

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public static function defaultConfigs(): array
    {
        return [
            'ticket_config' => [
                'show_logo' => true,
                'show_ice' => true,
                'show_qr' => false,
                'header' => '',
                'footer' => 'Merci de votre visite',
                'auto_print' => true,
            ],
            'kitchen_config' => [
                'group_by_category' => true,
                'show_prices' => false,
                'auto_print' => false,
                'header' => 'CUISINE',
            ],
            'advanced_config' => [
                'open_drawer' => false,
                'copies' => 1,
                'paper_width' => 80,
            ],
        ];
    }
}
