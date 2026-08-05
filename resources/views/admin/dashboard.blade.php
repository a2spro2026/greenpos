@extends('layouts.admin')

@section('title', 'Dashboard')
@section('breadcrumb', 'Console GreenPOS')
@section('heading', 'Dashboard')
@section('actions')
    <a href="{{ route('admin.registrations.index') }}" class="pa-btn pa-btn-ghost">Demandes</a>
    <a href="{{ route('admin.companies.create') }}" class="pa-btn pa-btn-primary">+ Nouvelle entreprise</a>
@endsection

@section('content')
<div class="pa-grid-4 mb-4">
    <a href="{{ route('admin.registrations.index', ['status' => 'EN_ATTENTE']) }}" class="pa-kpi">
        <div class="pa-kpi-top">
            <div class="label">Demandes en attente</div>
            <span class="pa-kpi-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </span>
        </div>
        <div class="value">{{ number_format($stats['registration_pending'] ?? 0) }}</div>
        <div class="hint">Inscriptions à valider</div>
    </a>
    <div class="pa-kpi">
        <div class="pa-kpi-top">
            <div class="label">Demandes aujourd’hui</div>
            <span class="pa-kpi-icon pa-kpi-icon--sky" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
        </div>
        <div class="value">{{ number_format($stats['registration_today'] ?? 0) }}</div>
        <div class="hint">Nouvelles inscriptions</div>
    </div>
    <div class="pa-kpi">
        <div class="pa-kpi-top">
            <div class="label">Demandes cette semaine</div>
            <span class="pa-kpi-icon pa-kpi-icon--sky" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0h6"/></svg>
            </span>
        </div>
        <div class="value">{{ number_format($stats['registration_week'] ?? 0) }}</div>
        <div class="hint">Depuis lundi</div>
    </div>
    <div class="pa-kpi">
        <div class="pa-kpi-top">
            <div class="label">Taux d’acceptation</div>
            <span class="pa-kpi-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <div class="value">{{ isset($stats['registration_acceptance_rate']) && $stats['registration_acceptance_rate'] !== null ? number_format($stats['registration_acceptance_rate'], 1).'%' : '—' }}</div>
        <div class="hint">Validées / (validées + refusées)</div>
    </div>
    <div class="pa-kpi">
        <div class="pa-kpi-top">
            <div class="label">Temps moyen de validation</div>
            <span class="pa-kpi-icon pa-kpi-icon--amber" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <div class="value" style="font-size:1.55rem">{{ $stats['registration_avg_validation_label'] ?? '—' }}</div>
        <div class="hint">Création → approbation</div>
    </div>
    <a href="{{ route('admin.companies.index', ['status' => 'active']) }}" class="pa-kpi">
        <div class="pa-kpi-top">
            <div class="label">Entreprises actives</div>
            <span class="pa-kpi-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            </span>
        </div>
        <div class="value">{{ number_format($stats['companies_active'] ?? $stats['companies_total'] ?? 0) }}</div>
        <div class="hint">Statut active</div>
    </a>
    <a href="{{ route('admin.companies.index', ['status' => 'inactive']) }}" class="pa-kpi">
        <div class="pa-kpi-top">
            <div class="label">Entreprises suspendues</div>
            <span class="pa-kpi-icon pa-kpi-icon--amber" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </span>
        </div>
        <div class="value">{{ number_format($stats['companies_suspended'] ?? $stats['suspended_companies'] ?? 0) }}</div>
        <div class="hint">Accès bloqué</div>
    </a>
    <a href="{{ route('admin.registrations.index', ['status' => 'REFUSEE']) }}" class="pa-kpi">
        <div class="pa-kpi-top">
            <div class="label">Entreprises refusées</div>
            <span class="pa-kpi-icon pa-kpi-icon--rose" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <div class="value">{{ number_format($stats['registration_rejected'] ?? 0) }}</div>
        <div class="hint">Demandes refusées</div>
    </a>
</div>

<div class="pa-grid-2">
    <div class="pa-card">
        <div class="pa-card-head">
            <h2 class="pa-card-title">Activité récente — Entreprises</h2>
            <a href="{{ route('admin.companies.index') }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Voir tout</a>
        </div>
        <div class="overflow-x-auto">
            <table class="pa-table">
                <thead>
                    <tr><th>Nom</th><th>Statut</th><th>Plan</th></tr>
                </thead>
                <tbody>
                    @forelse(($stats['recent_tenants'] ?? []) as $t)
                        <tr>
                            <td class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $t->name }}</td>
                            <td><span class="pa-badge {{ $t->status === 'active' ? 'pa-badge-ok' : ($t->status === 'suspended' ? 'pa-badge-danger' : 'pa-badge-warn') }}">{{ $t->status }}</span></td>
                            <td>{{ $t->currentSubscription?->plan?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-zinc-500">Aucune entreprise.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="pa-card">
        <div class="pa-card-head">
            <h2 class="pa-card-title">Paiements récents</h2>
            <a href="{{ route('admin.payments.index') }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Voir tout</a>
        </div>
        <div class="overflow-x-auto">
            <table class="pa-table">
                <thead>
                    <tr><th>Client</th><th>Montant</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    @forelse(($stats['recent_payments'] ?? []) as $p)
                        <tr>
                            <td>{{ $p->tenant?->name ?? '—' }}</td>
                            <td class="pa-mono">{{ number_format((float) $p->amount, 2, ',', ' ') }}</td>
                            <td><span class="pa-badge {{ $p->status === 'paid' ? 'pa-badge-ok' : 'pa-badge-muted' }}">{{ $p->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-zinc-500">Aucun paiement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
