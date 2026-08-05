<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasPaymentGateway extends Model
{
    protected $table = 'saas_payment_gateways';

    protected $fillable = [
        'code', 'name', 'is_enabled', 'is_sandbox', 'mode',
        'credentials', 'settings', 'webhook_secret',
        'last_tested_at', 'status', 'status_message',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_sandbox' => 'boolean',
            'credentials' => 'array',
            'settings' => 'array',
            'last_tested_at' => 'datetime',
        ];
    }
}
