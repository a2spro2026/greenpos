<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $table = 'ai_providers';

    protected $fillable = [
        'code', 'name', 'is_enabled', 'is_default', 'model', 'base_url',
        'credentials', 'settings', 'status', 'status_message',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'credentials' => 'array',
            'settings' => 'array',
        ];
    }
}
