<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSyncConflict extends Model
{
    protected $fillable = ['company_id', 'store_id', 'user_id', 'client_uuid', 'payload', 'message', 'status', 'attempts', 'pos_sale_id', 'resolved_by', 'offline_at', 'resolved_at'];

    protected $casts = ['payload' => 'array', 'offline_at' => 'datetime', 'resolved_at' => 'datetime'];

    public const STATUSES = ['open' => 'À traiter', 'synced' => 'Synchronisé', 'resolved' => 'Classé'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function sale(): BelongsTo { return $this->belongsTo(PosSale::class, 'pos_sale_id'); }

    public function total(): float
    {
        return round(collect($this->payload['payments'] ?? [])->sum(fn ($p) => (float) ($p['amount'] ?? 0)), 2);
    }
}
