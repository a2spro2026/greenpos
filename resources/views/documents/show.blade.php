@extends('layouts.app')

@section('title', $document->name)
@section('breadcrumb', 'Documents / Fiche')
@section('heading', $document->name)
@section('subtitle', strtoupper($document->extension).' · '.$document->humanSize().' · '.$document->moduleLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('documents.download')
            <a href="{{ route('documents.download', $document) }}" class="gp-btn-secondary">Télécharger</a>
        @endcan
        @if($document->isPreviewable())
            <a href="{{ route('documents.preview', $document) }}" target="_blank" class="gp-btn-secondary">Prévisualiser</a>
        @endif
        @can('documents.update')
            <form method="POST" action="{{ route('documents.favorite', $document) }}">@csrf<button class="gp-btn-secondary">{{ $document->is_favorite ? 'Retirer favori' : 'Favori' }}</button></form>
        @endcan
        @can('documents.archive')
            @if($document->status === 'active')
                <form method="POST" action="{{ route('documents.archive', $document) }}">@csrf<button class="gp-btn-secondary">Archiver</button></form>
            @else
                <form method="POST" action="{{ route('documents.restore', $document) }}">@csrf<button class="gp-btn-secondary">Restaurer</button></form>
            @endif
        @endcan
        @can('documents.delete')
            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Supprimer définitivement ?')">@csrf @method('DELETE')<button class="gp-btn-primary bg-rose-600 hover:bg-rose-700">Supprimer</button></form>
        @endcan
    </div>
@endsection

@section('content')
    @include('documents._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <article class="gp-card xl:col-span-2 space-y-4">
            @if($document->typeGroup() === 'image')
                <img src="{{ route('documents.preview', $document) }}" alt="{{ $document->name }}" class="max-h-96 w-full rounded-xl object-contain bg-gp-bg dark:bg-white/5">
            @elseif($document->typeGroup() === 'pdf')
                <iframe src="{{ route('documents.preview', $document) }}" class="h-[32rem] w-full rounded-xl border border-gp-border dark:border-white/10"></iframe>
            @else
                <div class="flex flex-col items-center justify-center gap-3 rounded-2xl bg-gp-bg py-16 dark:bg-white/5">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl text-sm font-bold uppercase {{ $document->iconColor() }}">{{ $document->extension }}</span>
                    <p class="text-sm text-gp-muted">Aperçu non disponible — téléchargez le fichier.</p>
                </div>
            @endif

            <dl class="grid gap-3 sm:grid-cols-2 text-sm border-t border-gp-border pt-4 dark:border-white/10">
                <div><dt class="text-xs text-gp-muted">Nom original</dt><dd class="font-semibold">{{ $document->original_name }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Auteur</dt><dd class="font-semibold">{{ $document->uploader?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Boutique</dt><dd class="font-semibold">{{ $document->store?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Dossier</dt><dd class="font-semibold">{{ $document->folder?->name ?? 'Racine' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Catégorie</dt><dd class="font-semibold">{{ $document->categoryLabel() }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Statut</dt><dd class="font-semibold">{{ $document->statusLabel() }}</dd></div>
            </dl>
            @if($document->tagsList())
                <div class="flex flex-wrap gap-2">
                    @foreach($document->tagsList() as $tag)
                        <span class="rounded-full bg-gp-primary-soft px-2.5 py-1 text-xs font-semibold text-gp-primary">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
            @if($document->description)
                <p class="text-sm text-gp-muted">{{ $document->description }}</p>
            @endif
        </article>

        <div class="space-y-4">
            @can('documents.update')
            <article class="gp-card space-y-3">
                <h2 class="text-sm font-bold">Renommer</h2>
                <form method="POST" action="{{ route('documents.rename', $document) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="name" value="{{ $document->name }}" class="gp-input flex-1" required>
                    <button class="gp-btn-secondary">OK</button>
                </form>
            </article>
            <article class="gp-card space-y-3">
                <h2 class="text-sm font-bold">Déplacer</h2>
                <form method="POST" action="{{ route('documents.move', $document) }}" class="space-y-2">
                    @csrf
                    <select name="folder_id" class="gp-input">
                        <option value="">Racine</option>
                        @foreach($folders as $folder)
                            <option value="{{ $folder->id }}" @selected($document->folder_id == $folder->id)>{{ $folder->name }}</option>
                        @endforeach
                    </select>
                    <button class="gp-btn-secondary w-full justify-center">Déplacer</button>
                </form>
            </article>
            <article class="gp-card space-y-3">
                <h2 class="text-sm font-bold">Métadonnées</h2>
                <form method="POST" action="{{ route('documents.update', $document) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $document->name }}">
                    <select name="module" class="gp-input">
                        @foreach(\App\Models\Document::MODULES as $k => $v)
                            <option value="{{ $k }}" @selected($document->module === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <select name="category" class="gp-input">
                        @foreach(\App\Models\Document::CATEGORIES as $k => $v)
                            <option value="{{ $k }}" @selected($document->category === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="tags" class="gp-input" value="{{ implode(', ', $document->tagsList()) }}" placeholder="tags">
                    <textarea name="description" rows="2" class="gp-input">{{ $document->description }}</textarea>
                    <button class="gp-btn-primary w-full justify-center">Enregistrer</button>
                </form>
            </article>
            @endcan
        </div>
    </div>
@endsection
