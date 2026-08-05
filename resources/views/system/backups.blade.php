@extends('layouts.app')

@section('title', 'Sauvegardes')
@section('breadcrumb', 'Système')
@section('heading', 'Sauvegardes')
@section('subtitle')
Manuelles et planifiées — isolation {{ $company->name }}
@endsection
@section('actions')
    <form method="POST" action="{{ route('system.backups.store') }}" class="inline">
        @csrf
        <input type="hidden" name="include_files" value="1">
        <button type="submit" class="gp-btn-primary">Sauvegarde manuelle</button>
    </form>
@endsection

@section('content')
@vite(['resources/css/system.css'])

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
@endif

<div class="sys-shell">
    @include('system._nav', ['active' => 'backups'])

    <div class="sys-grid sys-grid-2">
        <div class="sys-panel">
            <div class="sys-panel-hd"><h3>Planification automatique</h3></div>
            <div class="sys-panel-bd">
                <form method="POST" action="{{ route('system.backups.policy') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <label class="inline-flex items-center gap-2 text-sm font-medium">
                        <input type="checkbox" name="auto_backup" value="1" class="rounded border-gp-border" @checked($policy['auto_backup'] ?? false)>
                        Activer la sauvegarde automatique
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="gp-label">Fréquence</label>
                            <select name="frequency" class="gp-input">
                                @foreach($schedules as $val => $label)
                                    <option value="{{ $val }}" @selected(($policy['frequency'] ?? 'daily') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="gp-label">Rétention (jours)</label>
                            <input type="number" name="retention_days" min="1" max="365" class="gp-input" value="{{ $policy['retention_days'] ?? 30 }}">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="include_files" value="1" class="rounded border-gp-border" @checked($policy['include_files'] ?? true)>
                        Inclure fichiers / médias
                    </label>
                    <div>
                        <label class="gp-label">Note</label>
                        <textarea name="note" rows="2" class="gp-input" placeholder="Politique interne…">{{ $policy['note'] ?? '' }}</textarea>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-gp-muted">Dernière : {{ $policy['last_backup_at'] ?? 'Jamais' }}</p>
                        <button class="gp-btn-primary" type="submit">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="sys-panel">
            <div class="sys-panel-hd"><h3>Confiance & couverture</h3></div>
            <div class="sys-panel-bd space-y-3 text-sm text-gp-muted">
                <p>Chaque sauvegarde capture les données isolées de votre entreprise (tables multi-tenant + fichiers) dans une archive chiffrée côté stockage local.</p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>Planification quotidienne, hebdomadaire ou mensuelle</li>
                    <li>Historique avec date, taille, statut et durée</li>
                    <li>Restauration complète avec confirmation explicite</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="sys-panel">
        <div class="sys-panel-hd">
            <h3>Liste des sauvegardes</h3>
            <span class="text-xs text-gp-muted">{{ $backups->total() }} archive(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="sys-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Taille</th>
                        <th>Statut</th>
                        <th>Durée</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $b)
                        <tr>
                            <td class="font-semibold">{{ $b->code }}</td>
                            <td>
                                {{ $b->typeLabel() }}
                                @if($b->schedule)
                                    <span class="text-xs text-gp-muted">({{ $schedules[$b->schedule] ?? $b->schedule }})</span>
                                @endif
                            </td>
                            <td>{{ $b->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $b->sizeLabel() }}</td>
                            <td><span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $b->statusColor() }}">{{ $b->statusLabel() }}</span></td>
                            <td>{{ $b->durationLabel() }}</td>
                            <td class="text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('system.backups.show', $b) }}" class="text-xs font-semibold text-gp-primary">Aperçu</a>
                                @if($b->status === 'success')
                                    <a href="{{ route('system.backups.restore', $b) }}" class="text-xs font-semibold text-amber-700">Restaurer</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-gp-muted">Aucune sauvegarde. Lancez une sauvegarde manuelle.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($backups->hasPages())
            <div class="sys-panel-bd">{{ $backups->links() }}</div>
        @endif
    </div>
</div>
@endsection
