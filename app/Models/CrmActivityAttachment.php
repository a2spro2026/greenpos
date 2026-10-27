<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivityAttachment extends Model
{
    protected $fillable = [
        'crm_activity_id', 'uploaded_by', 'path', 'original_name', 'mime', 'size',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(CrmActivity::class, 'crm_activity_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
