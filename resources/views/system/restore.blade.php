@extends('layouts.app')

@section('title', 'Restauration')
@section('breadcrumb', 'Sauvegardes')
@section('heading', 'Restauration complète')
@section('subtitle')
Confirmation requise avant d’écraser les données de {{ $company->name }}
@endsection
@section('actions')
    <a href="{{ route('system.backups.show', $backup) }}" class="gp-btn-secondary">Annuler</a>
@endsection

@section('content')
@vite(['resources/css/system.css'])

@if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
@endif

<div class="sys-shell">
    @include('system._nav', ['active' => 'backups'])

    <div class="sys-warn-box">
        <strong>Attention.</strong> La restauration complète remplace les données métier de cette entreprise par le contenu de la sauvegarde
        <strong>{{ $backup->code }}</strong> ({{ $backup->created_at?->format('d/m/Y H:i') }}, {{ $backup->sizeLabel() }}).
        Cette action est irréversible.
    </div>

    <div class="sys-grid sys-grid-2">
        <div class="sys-panel">
            <div class="sys-panel-hd"><h3>Aperçu de la cible</h3></div>
            <div class="sys-panel-bd space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gp-muted">Code</span><span class="font-semibold">{{ $backup->code }}</span></div>
                <div class="flex justify-between"><span class="text-gp-muted">Statut</span><span>{{ $backup->statusLabel() }}</span></div>
                <div class="flex justify-between"><span class="text-gp-muted">Lignes</span><span>{{ number_format((int) ($manifest['row_count'] ?? 0)) }}</span></div>
                <div class="flex justify-between"><span class="text-gp-muted">Archive</span><span>{{ $exists ? 'OK' : 'Manquante' }}</span></div>
            </div>
        </div>

        <div class="sys-panel">
            <div class="sys-panel-hd"><h3>Confirmation</h3></div>
            <div class="sys-panel-bd">
                @if(! $exists || $backup->status !== 'success')
                    <p class="text-sm text-rose-700">Impossible de restaurer : archive invalide.</p>
                @else
                    <form method="POST" action="{{ route('system.backups.restore.run', $backup) }}" class="space-y-4">
                        @csrf
                        <label class="inline-flex items-start gap-2 text-sm">
                            <input type="checkbox" name="acknowledge" value="1" class="mt-1 rounded border-gp-border" required>
                            <span>Je comprends que les données actuelles de <strong>{{ $company->name }}</strong> seront remplacées.</span>
                        </label>
                        <div>
                            <label class="gp-label">Tapez <span class="font-mono text-rose-700">RESTAURER</span> pour confirmer</label>
                            <input type="text" name="confirmation" class="gp-input font-mono" autocomplete="off" required placeholder="RESTAURER">
                        </div>
                        <button type="submit" class="gp-btn-primary bg-rose-700 hover:bg-rose-800 border-rose-700">Lancer la restauration complète</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
