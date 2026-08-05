@extends('layouts.app')

@section('title', 'Aperçu sauvegarde')
@section('breadcrumb', 'Sauvegardes')
@section('heading', $backup->code)
@section('subtitle')
Aperçu de l’archive — {{ $company->name }}
@endsection
@section('actions')
    <a href="{{ route('system.backups') }}" class="gp-btn-secondary">Retour</a>
    @if($backup->status === 'success' && $exists)
        <a href="{{ route('system.backups.restore', $backup) }}" class="gp-btn-primary">Restaurer</a>
    @endif
@endsection

@section('content')
@vite(['resources/css/system.css'])

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="sys-shell">
    @include('system._nav', ['active' => 'backups'])

    <div class="sys-grid sys-grid-4">
        <div class="sys-metric">
            <div class="label">Date</div>
            <div class="value text-lg">{{ $backup->created_at?->format('d/m/Y') }}</div>
            <div class="hint">{{ $backup->created_at?->format('H:i:s') }}</div>
        </div>
        <div class="sys-metric">
            <div class="label">Taille</div>
            <div class="value text-lg">{{ $backup->sizeLabel() }}</div>
            <div class="hint">{{ number_format($backup->size_bytes) }} octets</div>
        </div>
        <div class="sys-metric">
            <div class="label">Statut</div>
            <div class="value text-lg"><span class="rounded-full px-2 py-0.5 text-sm font-bold {{ $backup->statusColor() }}">{{ $backup->statusLabel() }}</span></div>
            <div class="hint">{{ $backup->typeLabel() }}</div>
        </div>
        <div class="sys-metric">
            <div class="label">Durée</div>
            <div class="value text-lg">{{ $backup->durationLabel() }}</div>
            <div class="hint">{{ $backup->duration_ms }} ms</div>
        </div>
    </div>

    <div class="sys-grid sys-grid-2">
        <div class="sys-panel">
            <div class="sys-panel-hd"><h3>Manifeste</h3></div>
            <div class="sys-panel-bd space-y-2 text-sm">
                <div class="flex justify-between gap-3"><span class="text-gp-muted">Entreprise</span><span class="font-semibold">{{ $manifest['company_name'] ?? $company->name }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gp-muted">Lignes capturées</span><span class="font-semibold">{{ number_format((int) ($manifest['row_count'] ?? 0)) }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gp-muted">Fichiers inclus</span><span class="font-semibold">{{ !empty($manifest['files_included']) || $backup->include_files ? 'Oui' : 'Non' }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gp-muted">Archive</span><span class="font-semibold {{ $exists ? 'text-emerald-700' : 'text-rose-600' }}">{{ $exists ? 'Disponible' : 'Manquante' }}</span></div>
                @if($backup->error_message)
                    <div class="sys-warn-box mt-3">{{ $backup->error_message }}</div>
                @endif
            </div>
        </div>
        <div class="sys-panel">
            <div class="sys-panel-hd"><h3>Tables incluses</h3></div>
            <div class="sys-panel-bd">
                @php $tables = $manifest['tables'] ?? []; @endphp
                @if(empty($tables))
                    <p class="text-sm text-gp-muted">Aucun détail de tables.</p>
                @else
                    <div class="grid max-h-64 gap-1 overflow-y-auto text-xs sm:grid-cols-2">
                        @foreach($tables as $table => $count)
                            <div class="flex justify-between rounded-lg border border-gp-border/70 px-2 py-1.5 dark:border-white/10">
                                <span class="font-medium">{{ $table }}</span>
                                <span class="text-gp-muted">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('system.backups.destroy', $backup) }}" onsubmit="return confirm('Supprimer définitivement cette sauvegarde ?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="gp-btn-secondary text-rose-700">Supprimer l’archive</button>
    </form>
</div>
@endsection
