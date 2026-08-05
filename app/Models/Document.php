<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use SoftDeletes;

    public const MODULES = [
        'general' => 'Général',
        'products' => 'Produits',
        'customers' => 'Clients',
        'suppliers' => 'Fournisseurs',
        'purchases' => 'Achats',
        'sales' => 'Ventes',
        'invoices' => 'Facturation',
        'quotes' => 'Devis',
        'payments' => 'Paiements',
        'users' => 'Utilisateurs',
        'stock' => 'Stock',
        'pos' => 'POS',
    ];

    public const CATEGORIES = [
        'contract' => 'Contrat',
        'invoice' => 'Facture',
        'receipt' => 'Reçu',
        'identity' => 'Identité',
        'photo' => 'Photo',
        'report' => 'Rapport',
        'other' => 'Autre',
    ];

    public const STATUSES = [
        'active' => 'Actif',
        'archived' => 'Archivé',
    ];

    public const ALLOWED_EXTENSIONS = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp',
        'doc', 'docx', 'xls', 'xlsx', 'csv', 'zip',
    ];

    public const MIME_MAP = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv',
        'zip' => 'application/zip',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'folder_id', 'uploaded_by',
        'name', 'original_name', 'disk', 'path', 'mime', 'extension', 'size',
        'category', 'module', 'tags', 'description', 'is_favorite', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_favorite' => 'boolean',
            'archived_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('original_name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%");
        });
    }

    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $i === 0 ? 0 : 1).' '.$units[$i];
    }

    public function typeGroup(): string
    {
        $ext = strtolower((string) $this->extension);

        return match (true) {
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) => 'image',
            $ext === 'pdf' => 'pdf',
            in_array($ext, ['doc', 'docx'], true) => 'word',
            in_array($ext, ['xls', 'xlsx', 'csv'], true) => 'excel',
            $ext === 'zip' => 'zip',
            default => 'file',
        };
    }

    public function iconColor(): string
    {
        return match ($this->typeGroup()) {
            'pdf' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200',
            'image' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-200',
            'word' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-200',
            'excel' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
            'zip' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200',
            default => 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-200',
        };
    }

    public function isPreviewable(): bool
    {
        return in_array($this->typeGroup(), ['image', 'pdf'], true);
    }

    public function moduleLabel(): string
    {
        return self::MODULES[$this->module] ?? ($this->module ?: '—');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ($this->category ?: '—');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function tagsList(): array
    {
        return array_values(array_filter($this->tags ?? []));
    }

    /** Polymorphic helpers — attach via documentables */
    public function attachTo(Model $model): void
    {
        $exists = DB::table('documentables')
            ->where('document_id', $this->id)
            ->where('documentable_type', $model->getMorphClass())
            ->where('documentable_id', $model->getKey())
            ->exists();

        if (! $exists) {
            DB::table('documentables')->insert([
                'document_id' => $this->id,
                'documentable_type' => $model->getMorphClass(),
                'documentable_id' => $model->getKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function forDocumentable(Model $model): Builder
    {
        return static::query()
            ->whereIn('id', function ($q) use ($model) {
                $q->select('document_id')
                    ->from('documentables')
                    ->where('documentable_type', $model->getMorphClass())
                    ->where('documentable_id', $model->getKey());
            });
    }
}
