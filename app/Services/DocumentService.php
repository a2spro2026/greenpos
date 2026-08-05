<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    public function assertCompany(Document|DocumentFolder $model): void
    {
        $company = Workspace::company();
        if (! $company || $model->company_id !== $company->id) {
            abort(404);
        }
    }

    public function dashboardStats(): array
    {
        $company = Workspace::company();
        $base = Document::query()->forCompany($company->id);

        $totalSize = (clone $base)->sum('size');
        $recent = (clone $base)->where('status', 'active')->latest()->limit(8)->with(['uploader', 'folder'])->get();
        $favorites = (clone $base)->where('status', 'active')->where('is_favorite', true)->latest()->limit(6)->get();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'archived' => (clone $base)->where('status', 'archived')->count(),
            'favorites' => (clone $base)->where('is_favorite', true)->where('status', 'active')->count(),
            'folders' => DocumentFolder::query()->forCompany($company->id)->count(),
            'size' => (int) $totalSize,
            'size_human' => $this->humanSize((int) $totalSize),
            'recent' => $recent,
            'favorites_list' => $favorites,
            'by_module' => (clone $base)->where('status', 'active')
                ->selectRaw('module, COUNT(*) as cnt')
                ->groupBy('module')
                ->pluck('cnt', 'module'),
            'by_ext' => (clone $base)->where('status', 'active')
                ->selectRaw('extension, COUNT(*) as cnt')
                ->groupBy('extension')
                ->orderByDesc('cnt')
                ->limit(8)
                ->pluck('cnt', 'extension'),
        ];
    }

    public function foldersTree(?int $parentId = null)
    {
        return DocumentFolder::query()
            ->forCompany(Workspace::company()->id)
            ->where('parent_id', $parentId)
            ->withCount(['documents', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function createFolder(array $data): DocumentFolder
    {
        $company = Workspace::company();

        return DocumentFolder::query()->create([
            'company_id' => $company->id,
            'store_id' => $data['store_id'] ?? Workspace::store()?->id,
            'parent_id' => $data['parent_id'] ?? null,
            'created_by' => Auth::id(),
            'name' => $data['name'],
            'module' => $data['module'] ?? null,
            'color' => $data['color'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function renameFolder(DocumentFolder $folder, string $name): DocumentFolder
    {
        $this->assertCompany($folder);
        $folder->update(['name' => $name]);

        return $folder->fresh();
    }

    public function deleteFolder(DocumentFolder $folder): void
    {
        $this->assertCompany($folder);
        if ($folder->children()->exists() || $folder->documents()->exists()) {
            throw ValidationException::withMessages([
                'folder' => 'Videz le dossier avant de le supprimer.',
            ]);
        }
        $folder->delete();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, Document>
     */
    public function uploadMany(array $files, array $meta = []): array
    {
        $created = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $created[] = $this->upload($file, $meta);
            }
        }

        return $created;
    }

    public function upload(UploadedFile $file, array $meta = []): Document
    {
        $company = Workspace::company();
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        if (! in_array($ext, Document::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'files' => "Type non supporté : .{$ext}",
            ]);
        }

        $disk = 'public';
        $dir = 'dms/'.$company->id.'/'.now()->format('Y/m');
        $path = $file->store($dir, $disk);
        $name = $meta['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $document = Document::query()->create([
            'company_id' => $company->id,
            'store_id' => $meta['store_id'] ?? Workspace::store()?->id,
            'folder_id' => $meta['folder_id'] ?? null,
            'uploaded_by' => Auth::id(),
            'name' => $name,
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime' => $file->getMimeType() ?: (Document::MIME_MAP[$ext] ?? null),
            'extension' => $ext,
            'size' => $file->getSize() ?: 0,
            'category' => $meta['category'] ?? 'other',
            'module' => $meta['module'] ?? 'general',
            'tags' => $this->normalizeTags($meta['tags'] ?? []),
            'description' => $meta['description'] ?? null,
            'status' => 'active',
        ]);

        if (! empty($meta['documentable_type']) && ! empty($meta['documentable_id'])) {
            $this->attachMorph($document, $meta['documentable_type'], (int) $meta['documentable_id']);
        }

        return $document;
    }

    public function rename(Document $document, string $name): Document
    {
        $this->assertCompany($document);
        $document->update(['name' => $name]);

        return $document->fresh();
    }

    public function move(Document $document, ?int $folderId): Document
    {
        $this->assertCompany($document);
        if ($folderId) {
            $folder = DocumentFolder::query()->findOrFail($folderId);
            $this->assertCompany($folder);
        }
        $document->update(['folder_id' => $folderId]);

        return $document->fresh();
    }

    public function updateMeta(Document $document, array $data): Document
    {
        $this->assertCompany($document);
        $document->update([
            'name' => $data['name'] ?? $document->name,
            'category' => $data['category'] ?? $document->category,
            'module' => $data['module'] ?? $document->module,
            'tags' => array_key_exists('tags', $data) ? $this->normalizeTags($data['tags']) : $document->tags,
            'description' => $data['description'] ?? $document->description,
            'store_id' => array_key_exists('store_id', $data) ? $data['store_id'] : $document->store_id,
            'is_favorite' => array_key_exists('is_favorite', $data) ? (bool) $data['is_favorite'] : $document->is_favorite,
        ]);

        return $document->fresh();
    }

    public function toggleFavorite(Document $document): Document
    {
        $this->assertCompany($document);
        $document->update(['is_favorite' => ! $document->is_favorite]);

        return $document->fresh();
    }

    public function archive(Document $document): Document
    {
        $this->assertCompany($document);
        $document->update(['status' => 'archived', 'archived_at' => now()]);

        return $document->fresh();
    }

    public function restore(Document $document): Document
    {
        $this->assertCompany($document);
        $document->update(['status' => 'active', 'archived_at' => null]);

        return $document->fresh();
    }

    public function delete(Document $document): void
    {
        $this->assertCompany($document);
        if ($document->path) {
            Storage::disk($document->disk)->delete($document->path);
        }
        DB::table('documentables')->where('document_id', $document->id)->delete();
        $document->forceDelete();
    }

    public function attachMorph(Document $document, string $type, int $id): void
    {
        $map = $this->morphMap();
        if (! isset($map[$type])) {
            throw ValidationException::withMessages(['documentable_type' => 'Type de liaison invalide.']);
        }
        $class = $map[$type];
        $model = $class::query()->findOrFail($id);

        // Ensure same company when possible
        if (isset($model->company_id) && $model->company_id !== $document->company_id) {
            abort(403);
        }

        $document->attachTo($model);
    }

    public function morphMap(): array
    {
        return [
            'product' => \App\Models\Product::class,
            'customer' => \App\Models\Customer::class,
            'supplier' => \App\Models\Supplier::class,
            'purchase' => \App\Models\PurchaseOrder::class,
            'sale' => \App\Models\Sale::class,
            'invoice' => \App\Models\Invoice::class,
            'quote' => \App\Models\Quote::class,
            'payment' => \App\Models\InvoicePayment::class,
            'user' => \App\Models\User::class,
        ];
    }

    public function relatedFor(string $type, int $id)
    {
        $map = $this->morphMap();
        if (! isset($map[$type])) {
            abort(404);
        }
        $model = $map[$type]::query()->findOrFail($id);

        return Document::forDocumentable($model)
            ->forCompany(Workspace::company()->id)
            ->where('status', 'active')
            ->latest()
            ->get();
    }

    public function ensureDefaultFolders(): void
    {
        $company = Workspace::company();
        $defaults = [
            ['name' => 'Général', 'module' => 'general'],
            ['name' => 'Clients', 'module' => 'customers'],
            ['name' => 'Fournisseurs', 'module' => 'suppliers'],
            ['name' => 'Factures', 'module' => 'invoices'],
            ['name' => 'RH / Utilisateurs', 'module' => 'users'],
        ];

        foreach ($defaults as $i => $folder) {
            DocumentFolder::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'parent_id' => null,
                    'name' => $folder['name'],
                ],
                [
                    'module' => $folder['module'],
                    'created_by' => Auth::id(),
                    'sort_order' => $i,
                    'store_id' => Workspace::store()?->id,
                ]
            );
        }
    }

    protected function normalizeTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[,;]+/', $tags) ?: [];
        }
        if (! is_array($tags)) {
            return [];
        }

        return collect($tags)
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function humanSize(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
