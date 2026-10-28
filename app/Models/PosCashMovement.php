<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCashMovement extends Model
{
    public const DIRECTIONS = [
        'in' => 'Entrée (encaissement espèces)',
        'out' => 'Sortie (décaissement espèces)',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'pos_session_id', 'user_id', 'direction', 'amount', 'reason', 'client_uuid',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function session(): BelongsTo { return $this->belongsTo(PosSession::class, 'pos_session_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }
}
