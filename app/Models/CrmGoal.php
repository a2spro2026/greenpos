<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmGoal extends Model
{
    protected $table = 'crm_goals';

    protected $fillable = [
        'company_id', 'owner_user_id', 'period', 'year', 'month',
        'target_amount', 'target_deals', 'achieved_amount', 'achieved_deals',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'achieved_amount' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function progressPercent(): float
    {
        if ((float) $this->target_amount <= 0) {
            return 0;
        }

        return min(100, round(((float) $this->achieved_amount / (float) $this->target_amount) * 100, 1));
    }
}
