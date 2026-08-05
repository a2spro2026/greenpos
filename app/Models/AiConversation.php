<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'company_id', 'user_id', 'ai_prompt_id', 'title',
        'context_module', 'context_route', 'provider', 'status',
        'message_count', 'meta', 'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(AiPrompt::class, 'ai_prompt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'ai_conversation_id');
    }
}
