<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'enabled', 'frequency', 'types', 'categories', 'channels',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'types' => 'array',
            'categories' => 'array',
            'channels' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'frequency' => 'realtime',
            'types' => array_keys(AppNotification::TYPES),
            'categories' => array_keys(AppNotification::CATEGORIES),
            'channels' => [
                'internal' => true,
                'email' => true,
                'sms' => false,
                'whatsapp' => false,
                'push' => false,
            ],
        ];
    }
}
