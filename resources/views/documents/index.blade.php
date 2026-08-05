@extends('layouts.app')

@section('title', 'Explorateur documents')
@section('breadcrumb', 'Documents / Explorateur')
@section('heading', $currentFolder?->name ?? 'Explorateur')
@section('subtitle', 'Parcourez vos dossiers et fichiers.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('documents.index', array_merge(request()->except('view'), ['view' => 'grid'])) }}" class="gp-btn-secondary {{ $viewMode === 'grid' ? '!bg-gp-primary !text-white' : '' }}">Grille</a>
        <a href="{{ route('documents.index', array_merge(request()->except('view'), ['view' => 'list'])) }}" class="gp-btn-secondary {{ $viewMode === 'list' ? '!bg-gp-primary !text-white' : '' }}">Liste</a>
        @can('documents.create')
            <a href="{{ route('documents.upload', array_filter(['folder_id' => $currentFolder?->id])) }}" class="gp-btn-primary">Importer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('documents._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
        <a href="{{ route('documents.index') }}" class="font-semibold text-gp-primary hover:underline">Racine</a>
        @if($currentFolder)
            <span class="text-gp-muted">/</span>
            <span class="font-semibold">{{ $currentFolder->name }}</span>
        @endif
    </div>

    <form method="GET" action="{{ route('documents.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <input type="hidden" name="view" value="{{ $viewMode }}">
        @if($currentFolder)<input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">@endif
        <div class="min-w-[180px] flex-1">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher un fichier…" class="gp-input w-full">
        </div>
        <select name="module" class="gp-select w-40">
            <option value="">Module</option>
            @foreach($modules as $k => $v)<option value="{{ $k }}" @selected(($filters['module'] ?? '') === $k)>{{ $v }}</option>@endforeach
        </select>
        <select name="category" class="gp-select w-36">
            <option value="">Catégorie</option>
            @foreach($categories as $k => $v)<option value="{{ $k }}" @selected(($filters['category'] ?? '') === $k)>{{ $v }}</option>@endforeach
        </select>
        <select name="extension" class="gp-select w-28">
            <option value="">Type</option>
            @foreach(['pdf','jpg','png','docx','xlsx','csv','zip'] as $ext)
                <option value="{{ $ext }}" @selected(($filters['extension'] ?? '') === $ext)>{{ strtoupper($ext) }}</option>
            @endforeach
        </select>
        <select name="store_id" class="gp-select w-40">
            <option value="">Boutique</option>
            @foreach($stores as $st)<option value="{{ $st->id }}" @selected(($filters['store_id'] ?? '') == $st->id)>{{ $st->name }}</option>@endforeach
        </select>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="favorites" value="1" @checked(!empty($filters['favorites']))> Favoris</label>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="all" value="1" @checked(!empty($filters['all']) || !empty($filters['q']))> Tous dossiers</label>
        <button class="gp-btn-primary">Filtrer</button>
    </form>

    <div class="mb-6 grid gap-6 lg:grid-cols-4">
        <aside class="space-y-4 lg:col-span-1">
            @can('documents.folders')
            <article class="gp-card space-y-3">
                <h3 class="text-sm font-bold">Nouveau dossier</h3>
                <form method="POST" action="{{ route('documents.folders.store') }}" class="space-y-2">
                    @csrf
                    @if($currentFolder)<input type="hidden" name="parent_id" value="{{ $currentFolder->id }}">@endif
                    <input type="text" name="name" class="gp-input" placeholder="Nom du dossier" required>
                    <button class="gp-btn-secondary w-full justify-center text-xs">Créer</button>
                </form>
            </article>
            @endcan

            <article class="gp-card space-y-2">
                <h3 class="mb-2 text-sm font-bold">Dossiers</h3>
                @forelse($folders as $folder)
                    <a href="{{ route('documents.index', ['folder_id' => $folder->id, 'view' => $viewMode]) }}" class="flex items-center justify-between rounded-xl px-3 py-2 text-sm hover:bg-gp-primary-soft">
                        <span class="font-semibold">📁 {{ $folder->name }}</span>
                        <span class="text-xs text-gp-muted">{{ $folder->documents_count }}</span>
                    </a>
                @empty
                    <p class="text-xs text-gp-muted">Aucun sous-dossier.</p>
                @endforelse
            </article>
        </aside>

        <div class="lg:col-span-3">
            @if($viewMode === 'grid')
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($documents as $doc)
                        <a href="{{ route('documents.show', $doc) }}" class="gp-card group transition hover:-translate-y-0.5 hover:shadow-lg">
                            <div class="mb-3 flex items-start justify-between gap-2">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl text-xs font-bold uppercase {{ $doc->iconColor() }}">{{ $doc->extension }}</span>
                                @if($doc->is_favorite)<span class="text-amber-500">★</span>@endif
                            </div>
                            <p class="truncate font-bold group-hover:text-gp-primary">{{ $doc->name }}</p>
                            <p class="mt-1 text-xs text-gp-muted">{{ $doc->humanSize() }} · {{ $doc->moduleLabel() }}</p>
                            <p class="mt-1 text-[11px] text-gp-muted">{{ $doc->created_at->format('d/m/Y') }}</p>
                        </a>
                    @empty
                        <div class="gp-card col-span-full py-12 text-center text-sm text-gp-muted">Dossier vide. Importez des fichiers pour commencer.</div>
                    @endforelse
                </div>
            @else
                <section class="gp-card overflow-hidden p-0">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3">Nom</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Taille</th>
                                <th class="px-4 py-3">Module</th>
                                <th class="px-4 py-3">Auteur</th>
                                <th class="px-4 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gp-border dark:divide-white/10">
                            @forelse($documents as $doc)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('documents.show', $doc) }}" class="flex items-center gap-2 font-semibold text-gp-primary hover:underline">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-lg text-[10px] font-bold uppercase {{ $doc->iconColor() }}">{{ $doc->extension }}</span>
                                            {{ $doc->name }} @if($doc->is_favorite)★@endif
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 uppercase text-gp-muted">{{ $doc->extension }}</td>
                                    <td class="px-4 py-3 text-gp-muted">{{ $doc->humanSize() }}</td>
                                    <td class="px-4 py-3 text-gp-muted">{{ $doc->moduleLabel() }}</td>
                                    <td class="px-4 py-3 text-gp-muted">{{ $doc->uploader?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gp-muted">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-gp-muted">Aucun document.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            @endif

            @if($documents->hasPages())
                <div class="mt-4">{{ $documents->links() }}</div>
            @endif
        </div>
    </div>
@endsection
