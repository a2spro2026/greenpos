<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiPrompt extends Model
{
    protected $table = 'ai_prompts';

    public const PERSONAS = [
        'commercial' => 'Assistant Commercial',
        'comptable' => 'Assistant Comptable',
        'stock' => 'Assistant Stock',
        'pos' => 'Assistant POS',
        'direction' => 'Assistant Direction',
    ];

    protected $fillable = [
        'code', 'name', 'persona', 'icon', 'system_prompt',
        'capabilities', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'ai_prompt_id');
    }

    public function personaLabel(): string
    {
        return self::PERSONAS[$this->persona] ?? $this->name;
    }
}
