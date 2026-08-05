<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserDocument extends Model
{
    public const CATEGORIES = [
        'contract' => 'Contrat',
        'id' => 'Pièce d\'identité',
        'cv' => 'CV',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'company_id', 'user_id', 'uploaded_by', 'title', 'category',
        'file_path', 'original_name', 'mime_type', 'file_size',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
