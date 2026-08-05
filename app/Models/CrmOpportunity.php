<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CrmOpportunity extends Model
{
    use SoftDeletes;

    protected $table = 'crm_opportunities';

    public const STAGES = [
        'new' => 'Nouveau',
        'contacted' => 'Contact établi',
        'qualified' => 'Qualification',
        'proposal' => 'Proposition envoyée',
        'negotiation' => 'Négociation',
        'won' => 'Gagné',
        'lost' => 'Perdu',
    ];

    public const STAGE_COLORS = [
        'new' => 'bg-sky-500',
        'contacted' => 'bg-indigo-500',
        'qualified' => 'bg-violet-500',
        'proposal' => 'bg-amber-500',
        'negotiation' => 'bg-orange-500',
        'won' => 'bg-emerald-500',
        'lost' => 'bg-rose-500',
    ];

    public const STAGE_PROBABILITY = [
        'new' => 10,
        'contacted' => 20,
        'qualified' => 40,
        'proposal' => 60,
        'negotiation' => 75,
        'won' => 100,
        'lost' => 0,
    ];

    protected $fillable = [
        'company_id', 'store_id', 'crm_lead_id', 'customer_id', 'owner_user_id',
        'quote_id', 'invoice_id', 'number', 'name', 'stage', 'probability',
        'amount', 'currency', 'expected_close_on', 'closed_on', 'lost_reason',
        'description', 'pipeline_order', 'tags', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expected_close_on' => 'date',
            'closed_on' => 'date',
            'tags' => 'array',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CrmOpportunity $opp) {
            if (! $opp->number) {
                $opp->number = 'OP-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
            }
            if (! $opp->probability && isset(self::STAGE_PROBABILITY[$opp->stage])) {
                $opp->probability = self::STAGE_PROBABILITY[$opp->stage];
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'crm_opportunity_id');
    }

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }

    public function stageColor(): string
    {
        return self::STAGE_COLORS[$this->stage] ?? 'bg-slate-500';
    }

    public function weightedAmount(): float
    {
        return round((float) $this->amount * ((int) $this->probability / 100), 2);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->stage, ['won', 'lost'], true);
    }
}
