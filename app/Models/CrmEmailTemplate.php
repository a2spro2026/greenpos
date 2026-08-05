<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmEmailTemplate extends Model
{
    protected $table = 'crm_email_templates';

    protected $fillable = [
        'company_id', 'created_by', 'code', 'name', 'subject', 'body',
        'category', 'is_active', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CrmEmailLog::class, 'crm_email_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
