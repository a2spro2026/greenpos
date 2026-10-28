<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'company_id', 'name', 'token_hash', 'token_prefix',
        'abilities', 'last_used_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public static function generatePlainToken(): string
    {
        return 'gp_'.Str::random(40);
    }

    public static function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
