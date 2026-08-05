<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CrmEmailLog extends Model
{
    protected $table = 'crm_email_logs';

    public const STATUSES = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyé',
        'opened' => 'Ouvert',
        'failed' => 'Échec',
    ];

    protected $fillable = [
        'company_id', 'crm_email_template_id', 'crm_lead_id', 'crm_opportunity_id',
        'customer_id', 'user_id', 'to_email', 'subject', 'body',
        'status', 'sent_at', 'opened_at', 'open_count', 'tracking_token', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CrmEmailLog $log) {
            if (! $log->tracking_token) {
                $log->tracking_token = Str::random(40);
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CrmEmailTemplate::class, 'crm_email_template_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
