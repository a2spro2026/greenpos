@extends('layouts.app')

@section('title', 'Alertes système')
@section('breadcrumb', 'Système')
@section('heading', 'Alertes')
@section('subtitle')
Espace disque, sauvegardes, services et base de données
@endsection

@section('content')
@vite(['resources/css/system.css'])

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="sys-shell">
    @include('system._nav', ['active' => 'alerts'])

    <div class="sys-panel">
        <div class="sys-panel-hd">
            <h3>Toutes les alertes</h3>
            <span class="text-xs text-gp-muted">{{ $alerts->total() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="sys-table">
                <thead>
                    <tr>
                        <th>Sévérité</th>
                        <th>Type</th>
                        <th>Titre</th>
                        <th>Date</th>
                        <th>État</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td><span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $alert->severityColor() }}">{{ ucfirst($alert->severity) }}</span></td>
                            <td>{{ $alert->typeLabel() }}</td>
                            <td>
                                <div class="font-semibold">{{ $alert->title }}</div>
                                @if($alert->body)
                                    <div class="text-xs text-gp-muted">{{ \Illuminate\Support\Str::limit($alert->body, 100) }}</div>
                                @endif
                            </td>
                            <td>{{ $alert->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $alert->is_resolved ? 'Résolue' : 'Ouverte' }}</td>
                            <td class="text-right">
                                @unless($alert->is_resolved)
                                    <form method="POST" action="{{ route('system.alerts.resolve', $alert) }}">
                                        @csrf
                                        <button class="text-xs font-semibold text-gp-primary">Résoudre</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-gp-muted">Aucune alerte.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($alerts->hasPages())
            <div class="sys-panel-bd">{{ $alerts->links() }}</div>
        @endif
    </div>
</div>
@endsection
