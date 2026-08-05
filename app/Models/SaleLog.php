<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLog extends Model
{
    protected $fillable = ['sale_id', 'user_id', 'action', 'message', 'meta'];

    protected function casts(): array { return ['meta' => 'array']; }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
