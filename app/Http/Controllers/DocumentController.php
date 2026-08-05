<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Store;
use App\Services\DocumentService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documents)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('documents.view');
        $this->documents->ensureDefaultFolders();
        $stats = $this->documents->dashboardStats();

        return view('documents.dashboard', compact('stats'));
    }

    public function index(Request $request): View
    {
        $this->authorize('documents.view');
        $this->documents->ensureDefaultFolders();
        $company = Workspace::company();

        $folderId = $request->filled('folder_id') ? $request->integer('folder_id') : null;
        $viewMode = $request->string('view', 'grid')->toString() === 'list' ? 'list' : 'grid';

        $sort = $request->string('sort', 'created_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['name', 'created_at', 'size', 'extension'], true)) {
            $sort = 'created_at';
        }

        $query = Document::query()
            ->forCompany($company->id)
            ->with(['uploader', 'folder', 'store'])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', 'active'))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('extension'), fn ($q) => $q->where('extension', $request->string('extension')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->boolean('favorites'), fn ($q) => $q->where('is_favorite', true))
            ->when($request->filled('tag'), function ($q) use ($request) {
                $tag = $request->string('tag')->toString();
                $q->whereJsonContains('tags', $tag);
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('author'), fn ($q) => $q->where('uploaded_by', $request->integer('author')));

        // Folder browsing: only apply when not searching globally
        if (! $request->filled('q') && ! $request->boolean('all')) {
            $query->where('folder_id', $folderId);
        }

        $documents = $query->orderBy($sort, $dir)->paginate(24)->withQueryString();
        $folders = $this->documents->foldersTree($folderId);
        $currentFolder = $folderId ? DocumentFolder::query()->forCompany($company->id)->findOrFail($folderId) : null;
        $allFolders = DocumentFolder::query()->forCompany($company->id)->orderBy('name')->get();
        $stores = Store::query()->where('company_id', $company->id)->orderBy('name')->get();

        return view('documents.index', [
            'documents' => $documents,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'allFolders' => $allFolders,
            'stores' => $stores,
            'viewMode' => $viewMode,
            'modules' => Document::MODULES,
            'categories' => Document::CATEGORIES,
            'filters' => $request->only(['q', 'status', 'module', 'category', 'extension', 'store_id', 'favorites', 'tag', 'from', 'to', 'author', 'folder_id', 'view', 'all', 'sort', 'dir']),
        ]);
    }

    public function uploadForm(Request $request): View
    {
        $this->authorize('documents.create');
        $company = Workspace::company();
        $folders = DocumentFolder::query()->forCompany($company->id)->orderBy('name')->get();
        $stores = Store::query()->where('company_id', $company->id)->orderBy('name')->get();

        return view('documents.upload', [
            'folders' => $folders,
            'stores' => $stores,
            'modules' => Document::MODULES,
            'categories' => Document::CATEGORIES,
            'folder_id' => $request->integer('folder_id') ?: null,
            'documentable_type' => $request->string('documentable_type')->toString() ?: null,
            'documentable_id' => $request->integer('documentable_id') ?: null,
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->authorize('documents.create');

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:20480'],
            'folder_id' => ['nullable', 'integer', 'exists:document_folders,id'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'module' => ['nullable', 'string', 'max:64'],
            'category' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'documentable_type' => ['nullable', 'string'],
            'documentable_id' => ['nullable', 'integer'],
        ]);

        $meta = [
            'folder_id' => $request->input('folder_id'),
            'store_id' => $request->input('store_id'),
            'module' => $request->input('module', 'general'),
            'category' => $request->input('category', 'other'),
            'tags' => $request->input('tags'),
            'description' => $request->input('description'),
            'documentable_type' => $request->input('documentable_type'),
            'documentable_id' => $request->input('documentable_id'),
        ];

        $created = $this->documents->uploadMany($request->file('files', []), $meta);

        return redirect()
            ->route('documents.index', array_filter(['folder_id' => $meta['folder_id']]))
            ->with('success', count($created).' document(s) importé(s).');
    }

    public function show(Document $document): View
    {
        $this->authorize('documents.view');
        $this->documents->assertCompany($document);
        $document->load(['uploader', 'folder', 'store']);
        $folders = DocumentFolder::query()->forCompany(Workspace::company()->id)->orderBy('name')->get();

        return view('documents.show', compact('document', 'folders'));
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('documents.download');
        $this->documents->assertCompany($document);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function preview(Document $document): View|\Illuminate\Http\Response
    {
        $this->authorize('documents.view');
        $this->documents->assertCompany($document);

        if ($document->typeGroup() === 'image') {
            return response(Storage::disk($document->disk)->get($document->path), 200, [
                'Content-Type' => $document->mime ?: 'image/jpeg',
                'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
            ]);
        }

        if ($document->typeGroup() === 'pdf') {
            return response(Storage::disk($document->disk)->get($document->path), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
            ]);
        }

        return redirect()->route('documents.download', $document);
    }

    public function rename(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('documents.update');
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $this->documents->rename($document, $data['name']);

        return back()->with('success', 'Document renommé.');
    }

    public function move(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('documents.update');
        $data = $request->validate(['folder_id' => ['nullable', 'integer', 'exists:document_folders,id']]);
        $this->documents->move($document, $data['folder_id'] ?? null);

        return back()->with('success', 'Document déplacé.');
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('documents.update');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:64'],
            'module' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'is_favorite' => ['nullable', 'boolean'],
        ]);
        $data['is_favorite'] = $request->boolean('is_favorite');
        $this->documents->updateMeta($document, $data);

        return back()->with('success', 'Document mis à jour.');
    }

    public function favorite(Document $document): RedirectResponse
    {
        $this->authorize('documents.update');
        $this->documents->toggleFavorite($document);

        return back()->with('success', 'Favori mis à jour.');
    }

    public function archive(Document $document): RedirectResponse
    {
        $this->authorize('documents.archive');
        $this->documents->archive($document);

        return back()->with('success', 'Document archivé.');
    }

    public function restore(Document $document): RedirectResponse
    {
        $this->authorize('documents.archive');
        $this->documents->restore($document);

        return back()->with('success', 'Document restauré.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('documents.delete');
        $this->documents->delete($document);

        return redirect()->route('documents.index')->with('success', 'Document supprimé.');
    }

    public function storeFolder(Request $request): RedirectResponse
    {
        $this->authorize('documents.folders');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:document_folders,id'],
            'module' => ['nullable', 'string', 'max:64'],
        ]);
        $folder = $this->documents->createFolder($data);

        return redirect()->route('documents.index', ['folder_id' => $folder->id])->with('success', 'Dossier créé.');
    }

    public function renameFolder(Request $request, DocumentFolder $folder): RedirectResponse
    {
        $this->authorize('documents.folders');
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $this->documents->renameFolder($folder, $data['name']);

        return back()->with('success', 'Dossier renommé.');
    }

    public function destroyFolder(DocumentFolder $folder): RedirectResponse
    {
        $this->authorize('documents.folders');
        $parent = $folder->parent_id;
        $this->documents->deleteFolder($folder);

        return redirect()->route('documents.index', array_filter(['folder_id' => $parent]))->with('success', 'Dossier supprimé.');
    }

    public function related(string $type, int $id): View
    {
        $this->authorize('documents.view');
        $documents = $this->documents->relatedFor($type, $id);

        return view('documents.related', [
            'documents' => $documents,
            'type' => $type,
            'id' => $id,
        ]);
    }
}
