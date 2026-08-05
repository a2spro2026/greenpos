<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    protected $fillable = [
        'sale_id', 'created_by', 'number', 'type', 'reason',
        'notes', 'total_returned', 'restock', 'returned_at',
    ];

    protected function casts(): array
    {
        return ['total_returned' => 'decimal:2', 'restock' => 'boolean', 'returned_at' => 'datetime'];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function returnLines(): HasMany { return $this->hasMany(SaleReturnLine::class); }
}
