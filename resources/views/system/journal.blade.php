@extends('layouts.app')

@section('title', 'Journal système')
@section('breadcrumb', 'Système')
@section('heading', 'Journal')
@section('subtitle')
Historique des sauvegardes, restaurations, erreurs et incidents
@endsection

@section('content')
@vite(['resources/css/system.css'])

<div class="sys-shell">
    @include('system._nav', ['active' => 'journal'])

    <div class="flex flex-wrap gap-2">
        @foreach([null => 'Tout', 'backup' => 'Sauvegardes', 'restore' => 'Restaurations', 'error' => 'Erreurs', 'incident' => 'Incidents', 'health' => 'Santé'] as $key => $label)
            <a href="{{ route('system.journal', array_filter(['category' => $key])) }}"
               class="sys-tab {{ ($category ?? null) === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="sys-panel">
        <div class="sys-panel-hd"><h3>Événements</h3></div>
        <div class="overflow-x-auto">
            <table class="sys-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Catégorie</th>
                        <th>Sévérité</th>
                        <th>Titre</th>
                        <th>Utilisateur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $ev)
                        <tr>
                            <td class="whitespace-nowrap">{{ $ev->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $ev->categoryLabel() }}</td>
                            <td><span class="sys-pill {{ $ev->severity === 'critical' ? 'crit' : ($ev->severity === 'warning' ? 'warn' : 'info') }}">{{ $ev->severity }}</span></td>
                            <td>
                                <div class="font-semibold">{{ $ev->title }}</div>
                                @if($ev->body)
                                    <div class="text-xs text-gp-muted">{{ \Illuminate\Support\Str::limit($ev->body, 120) }}</div>
                                @endif
                            </td>
                            <td>{{ $ev->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-gp-muted">Aucun événement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
