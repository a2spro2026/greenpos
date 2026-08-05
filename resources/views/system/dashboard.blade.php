@extends('layouts.app')

@section('title', 'Santé système')
@section('breadcrumb', 'Administration')
@section('heading', 'Sauvegardes & Santé')
@section('subtitle')
Surveillance et protection des données — {{ $company->name }}
@endsection
@section('actions')
    <form method="POST" action="{{ route('system.health.refresh') }}" class="inline">
        @csrf
        <button type="submit" class="gp-btn-secondary">Actualiser</button>
    </form>
    <a href="{{ route('system.backups') }}" class="gp-btn-primary">Sauvegardes</a>
@endsection

@section('content')
@vite(['resources/css/system.css'])

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

@php
    $overall = $health['overall'] ?? 'healthy';
    $overallLabel = match($overall) {
        'healthy' => 'Système sain',
        'degraded' => 'État dégradé',
        'critical' => 'État critique',
        default => $overall,
    };
    $diskPct = (float) data_get($health, 'disk.used_percent', 0);
@endphp

<div class="sys-shell">
    @include('system._nav', ['active' => 'dashboard'])

    <div class="sys-hero">
        <div class="relative z-10 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-teal-100/80">
                    <span class="sys-live {{ $overall === 'healthy' ? '' : 'bad' }}"></span>
                    GreenPOS System Health
                </div>
                <h2>{{ $overallLabel }}</h2>
                <p>Base de données, stockage, services et sauvegardes sont surveillés en continu pour protéger les données de votre entreprise.</p>
            </div>
            <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm backdrop-blur">
                <div class="text-[10px] font-bold uppercase tracking-wider text-teal-100/70">Dernière vérif.</div>
                <div class="mt-1 font-semibold">{{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="sys-grid sys-grid-4">
        <div class="sys-metric">
            <div class="label">Base de données</div>
            <div class="value">{{ ($health['database_status'] ?? '') === 'ok' ? 'OK' : 'DOWN' }}</div>
            <div class="hint">{{ config('database.default') }} · {{ $health['response_ms'] ?? 0 }} ms</div>
        </div>
        <div class="sys-metric">
            <div class="label">Stockage</div>
            <div class="value">{{ number_format($diskPct, 1) }}%</div>
            <div class="hint mt-2">
                <div class="sys-bar {{ $diskPct >= 90 ? 'crit' : '' }}"><span style="width: {{ min(100, $diskPct) }}%"></span></div>
                <div class="mt-1">Libre : {{ \App\Services\SystemHealthService::formatBytes((int) data_get($health, 'disk.free_bytes', 0)) }}</div>
            </div>
        </div>
        <div class="sys-metric">
            <div class="label">Temps de réponse</div>
            <div class="value">{{ $health['response_ms'] ?? 0 }}<span class="text-base font-semibold text-gp-muted"> ms</span></div>
            <div class="hint">Requête diagnostic DB</div>
        </div>
        <div class="sys-metric">
            <div class="label">Alertes ouvertes</div>
            <div class="value">{{ $alerts->count() }}</div>
            <div class="hint">
                @if(($policy['auto_backup'] ?? false))
                    Auto {{ $policy['frequency'] ?? 'daily' }}
                @else
                    Sauvegarde auto désactivée
                @endif
            </div>
        </div>
    </div>

    <div class="sys-grid sys-grid-3">
        <div class="sys-panel lg:col-span-1">
            <div class="sys-panel-hd">
                <h3>Disponibilité des services</h3>
            </div>
            <div class="sys-panel-bd">
                @foreach(($health['services'] ?? []) as $key => $svc)
                    @php $st = $svc['status'] ?? 'ok'; @endphp
                    <div class="sys-service">
                        <div>
                            <div class="text-sm font-semibold">{{ $svc['label'] ?? $key }}</div>
                            <div class="text-xs text-gp-muted truncate max-w-[14rem]">{{ $svc['detail'] ?? '' }}</div>
                        </div>
                        <span class="sys-pill {{ $st === 'ok' ? 'ok' : ($st === 'degraded' ? 'warn' : 'crit') }}">{{ strtoupper($st) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sys-panel lg:col-span-2">
            <div class="sys-panel-hd">
                <h3>Sauvegardes récentes</h3>
                <a href="{{ route('system.backups') }}" class="text-xs font-semibold text-gp-primary">Voir tout</a>
            </div>
            <div class="overflow-x-auto">
                <table class="sys-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Taille</th>
                            <th>Statut</th>
                            <th>Durée</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBackups as $b)
                            <tr>
                                <td><a href="{{ route('system.backups.show', $b) }}" class="font-semibold text-gp-primary">{{ $b->code }}</a></td>
                                <td>{{ $b->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $b->sizeLabel() }}</td>
                                <td><span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $b->statusColor() }}">{{ $b->statusLabel() }}</span></td>
                                <td>{{ $b->durationLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-gp-muted">Aucune sauvegarde pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="sys-grid sys-grid-2">
        <div class="sys-panel">
            <div class="sys-panel-hd">
                <h3>Alertes actives</h3>
                <a href="{{ route('system.alerts') }}" class="text-xs font-semibold text-gp-primary">Gérer</a>
            </div>
            <div class="sys-panel-bd space-y-3">
                @forelse($alerts as $alert)
                    <div class="flex items-start justify-between gap-3 rounded-xl border border-gp-border/80 px-3 py-2.5 dark:border-white/10">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="sys-pill {{ $alert->severity === 'critical' ? 'crit' : ($alert->severity === 'warning' ? 'warn' : 'info') }}">{{ $alert->typeLabel() }}</span>
                                <span class="text-sm font-semibold">{{ $alert->title }}</span>
                            </div>
                            @if($alert->body)
                                <p class="mt-1 text-xs text-gp-muted">{{ \Illuminate\Support\Str::limit($alert->body, 120) }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('system.alerts.resolve', $alert) }}">
                            @csrf
                            <button class="text-xs font-semibold text-gp-primary">Résoudre</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gp-muted">Aucune alerte ouverte. Tout est nominal.</p>
                @endforelse
            </div>
        </div>

        <div class="sys-panel">
            <div class="sys-panel-hd">
                <h3>Historique des incidents</h3>
                <a href="{{ route('system.journal', ['category' => 'incident']) }}" class="text-xs font-semibold text-gp-primary">Journal</a>
            </div>
            <div class="sys-panel-bd space-y-3">
                @forelse($incidents as $ev)
                    <div class="flex gap-3">
                        <div class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $ev->severity === 'critical' ? 'bg-rose-500' : 'bg-amber-400' }}"></div>
                        <div>
                            <div class="text-sm font-semibold">{{ $ev->title }}</div>
                            <div class="text-xs text-gp-muted">{{ $ev->created_at?->format('d/m/Y H:i') }} · {{ $ev->categoryLabel() }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gp-muted">Aucun incident récent.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
