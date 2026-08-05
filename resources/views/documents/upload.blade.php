@extends('layouts.app')

@section('title', 'Importer des documents')
@section('breadcrumb', 'Documents / Import')
@section('heading', 'Importer')
@section('subtitle', 'Téléversement multiple — PDF, images, Office, CSV, ZIP.')

@section('content')
    @include('documents._nav')

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-6 max-w-3xl" id="dms-upload-form">
        @csrf
        @if($documentable_type)<input type="hidden" name="documentable_type" value="{{ $documentable_type }}">@endif
        @if($documentable_id)<input type="hidden" name="documentable_id" value="{{ $documentable_id }}">@endif

        <article class="gp-card">
            <label id="dms-dropzone" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gp-border bg-gp-bg px-6 py-16 text-center transition hover:border-gp-primary/50 dark:border-white/10 dark:bg-white/5">
                <p class="text-sm font-bold">Glissez-déposez vos fichiers ici</p>
                <p class="mt-1 text-xs text-gp-muted">ou cliquez pour sélectionner (max 20 Mo / fichier)</p>
                <input type="file" name="files[]" id="dms-files" class="sr-only" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.csv,.zip" required>
                <p id="dms-file-count" class="mt-4 text-xs font-semibold text-gp-primary"></p>
            </label>
        </article>

        <article class="gp-card grid gap-4 sm:grid-cols-2">
            <div>
                <label class="gp-label">Dossier</label>
                <select name="folder_id" class="gp-input">
                    <option value="">Racine</option>
                    @foreach($folders as $folder)
                        <option value="{{ $folder->id }}" @selected($folder_id == $folder->id)>{{ $folder->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="gp-label">Boutique</label>
                <select name="store_id" class="gp-input">
                    <option value="">—</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" @selected(($workspaceStore->id ?? null) == $st->id)>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="gp-label">Module</label>
                <select name="module" class="gp-input">
                    @foreach($modules as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="gp-label">Catégorie</label>
                <select name="category" class="gp-input">
                    @foreach($categories as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="gp-label">Tags (séparés par virgule)</label>
                <input type="text" name="tags" class="gp-input" placeholder="contrat, 2026, important">
            </div>
            <div class="sm:col-span-2">
                <label class="gp-label">Description</label>
                <textarea name="description" rows="2" class="gp-input"></textarea>
            </div>
        </article>

        <div class="flex justify-end gap-2">
            <a href="{{ route('documents.index') }}" class="gp-btn-secondary">Annuler</a>
            <button type="submit" class="gp-btn-primary">Téléverser</button>
        </div>
    </form>

    <script>
        const input = document.getElementById('dms-files');
        const zone = document.getElementById('dms-dropzone');
        const count = document.getElementById('dms-file-count');
        const update = () => { count.textContent = input.files?.length ? input.files.length + ' fichier(s) sélectionné(s)' : ''; };
        input?.addEventListener('change', update);
        ['dragenter','dragover'].forEach(ev => zone?.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('border-gp-primary'); }));
        ['dragleave','drop'].forEach(ev => zone?.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('border-gp-primary'); }));
        zone?.addEventListener('drop', e => {
            if (e.dataTransfer?.files?.length) {
                input.files = e.dataTransfer.files;
                update();
            }
        });
    </script>
@endsection
