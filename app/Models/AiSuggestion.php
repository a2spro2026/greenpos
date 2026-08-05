<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSuggestion extends Model
{
    protected $table = 'ai_suggestions';

    protected $fillable = [
        'company_id', 'user_id', 'category', 'title', 'body',
        'module', 'action_url', 'action_label', 'priority',
        'is_read', 'is_dismissed', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_dismissed' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
