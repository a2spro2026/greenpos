<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountEntryLine extends Model
{
    protected $fillable = [
        'account_entry_id', 'account_id', 'debit', 'credit', 'label',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(AccountEntry::class, 'account_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountAccount::class, 'account_id');
    }
}
