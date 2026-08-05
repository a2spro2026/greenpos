<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasPlatformSnapshot extends Model
{
    protected $table = 'saas_platform_snapshots';

    protected $fillable = [
        'captured_at', 'cpu_percent', 'memory_percent', 'disk_percent',
        'storage_used_bytes', 'services', 'overall_status', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'cpu_percent' => 'decimal:2',
            'memory_percent' => 'decimal:2',
            'disk_percent' => 'decimal:2',
            'services' => 'array',
            'meta' => 'array',
        ];
    }
}
