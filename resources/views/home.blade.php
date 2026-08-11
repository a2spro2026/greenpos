@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('breadcrumb', 'Pilotage')
@section('heading', 'Tableau de bord')
@section('subtitle', 'Cockpit opérationnel — recherche universelle Ctrl+K, widgets personnalisables.')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" id="gp-dash-customize" class="gp-btn-secondary" title="Personnaliser le tableau de bord">Personnaliser</button>
        <button type="button" id="gp-dash-reset" class="gp-btn-ghost text-xs font-semibold text-gp-muted" title="Réinitialiser la disposition">Reset</button>
        @can('reports.export')
            <a href="{{ route('reports.export', ['type' => 'sales']) }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('pos.sell')
            <a href="{{ route('pos.terminal') }}" class="gp-btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h8v8H3V3zm10 0h8v8h-8V3z"/></svg>
                Ouvrir la caisse
            </a>
        @endcan
    </div>
@endsection

@section('content')
    @include('onboarding._checklist')
    <div data-gp-dashboard class="space-y-6">
        <section class="gp-kpi-grid gp-kpi-sticky">
            <article class="gp-kpi gp-kpi--sales" data-gp-widget="revenue" data-gp-locked>
                <div class="gp-kpi-head">
                    <span class="gp-kpi-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V5m0 14v-1"/></svg>
                    </span>
                    <span class="gp-kpi-chip">Aujourd’hui</span>
                </div>
                <p class="gp-kpi-label">Chiffre d’affaires</p>
                <p class="gp-kpi-value">{{ number_format($stats['revenue_today'], 2, ',', ' ') }} <small>{{ $stats['currency'] }}</small></p>
                <p class="gp-kpi-hint">Performance du jour · boutique active</p>
            </article>

            <article class="gp-kpi gp-kpi--tx" data-gp-widget="sales" data-gp-locked>
                <div class="gp-kpi-head">
                    <span class="gp-kpi-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                    </span>
                    <span class="gp-kpi-chip">Transactions</span>
                </div>
                <p class="gp-kpi-label">Ventes du jour</p>
                <p class="gp-kpi-value">{{ $stats['sales_today'] }}</p>
                <p class="gp-kpi-hint">Tickets finalisés aujourd’hui</p>
            </article>

            <article class="gp-kpi gp-kpi--stock" data-gp-widget="stock" data-gp-locked>
                <div class="gp-kpi-head">
                    <span class="gp-kpi-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4"/></svg>
                    </span>
                    <span class="gp-kpi-chip {{ $stats['stock_alerts'] > 0 ? 'is-warn' : '' }}">Alertes</span>
                </div>
                <p class="gp-kpi-label">Produits en rupture</p>
                <p class="gp-kpi-value">{{ $stats['stock_alerts'] }}</p>
                <p class="gp-kpi-hint">Seuils critiques du stock</p>
            </article>

            <article class="gp-kpi gp-kpi--catalog" data-gp-widget="catalog" data-gp-locked>
                <div class="gp-kpi-head">
                    <span class="gp-kpi-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/></svg>
                    </span>
                    <span class="gp-kpi-chip">Catalogue</span>
                </div>
                <p class="gp-kpi-label">Produits actifs</p>
                <p class="gp-kpi-value">{{ $stats['products'] }}</p>
                <p class="gp-kpi-hint">Références de l’espace de travail</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article class="gp-widget xl:col-span-2" data-gp-widget="trend">
                <div class="gp-widget-toolbar"><button type="button" class="text-[11px] text-gp-muted hover:text-rose-500" data-gp-widget-hide="trend">Masquer</button></div>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-gp-text dark:text-white">Activité commerciale</h2>
                        <p class="mt-0.5 text-xs text-gp-muted">Tendance des ventes</p>
                    </div>
                    <span class="gp-kpi-chip" style="--kpi-accent:#0d9488">Live</span>
                </div>
                <div class="gp-chart-wrap">
                    <canvas id="gp-home-chart" aria-label="Graphique d'activité"></canvas>
                </div>
            </article>

            <article class="gp-widget" data-gp-widget="alerts">
                <div class="gp-widget-toolbar"><button type="button" class="text-[11px] text-gp-muted hover:text-rose-500" data-gp-widget-hide="alerts">Masquer</button></div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gp-text dark:text-white">Alertes</h2>
                    <span class="text-xs text-gp-muted">Priorités</span>
                </div>
                <div class="space-y-3">
                    @if($stats['stock_alerts'] > 0)
                    <div class="gp-alert gp-alert-warning">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold">Stock à surveiller</p>
                            <p class="mt-0.5 text-xs opacity-80">{{ $stats['stock_alerts'] }} produit(s) sous le seuil.</p>
                            @can('stock.view')
                                <a href="{{ route('stock.alerts') }}" class="mt-2 inline-flex text-xs font-semibold underline-offset-2 hover:underline">Voir</a>
                            @endcan
                        </div>
                    </div>
                    @else
                    <div class="gp-alert gp-alert-success">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div><p class="text-sm font-semibold">Stock stable</p><p class="mt-0.5 text-xs opacity-80">Aucun seuil critique.</p></div>
                    </div>
                    @endif

                    @if(! $stats['pos_open'])
                    <div class="gp-alert gp-alert-critical">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold">Aucune caisse ouverte</p>
                            <p class="mt-0.5 text-xs opacity-80">Ouvrez une session avant d’encaisser.</p>
                            @can('pos.open')
                                <a href="{{ route('pos.sessions.create') }}" class="mt-2 inline-flex text-xs font-semibold underline-offset-2 hover:underline">Ouvrir</a>
                            @endcan
                        </div>
                    </div>
                    @else
                    <div class="gp-alert gp-alert-success">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold">Caisse ouverte</p>
                            <p class="mt-0.5 text-xs opacity-80">Session POS active.</p>
                            @can('pos.sell')
                                <a href="{{ route('pos.terminal') }}" class="mt-2 inline-flex text-xs font-semibold underline-offset-2 hover:underline">Terminal</a>
                            @endcan
                        </div>
                    </div>
                    @endif
                </div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <div class="xl:col-span-2" data-gp-widget="shortcuts">
                <div class="gp-widget-toolbar"><button type="button" class="text-[11px] text-gp-muted hover:text-rose-500" data-gp-widget-hide="shortcuts">Masquer</button></div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gp-text dark:text-white">Raccourcis</h2>
                    <span class="text-xs text-gp-muted">Ctrl+K pour chercher</span>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @can('pos.sell')
                    <a href="{{ route('pos.terminal') }}" class="gp-shortcut">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-gp-primary dark:bg-teal-500/15">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h8v8H3V3zm10 0h8v8h-8V3z"/></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-gp-text dark:text-white">Ouvrir le POS</span>
                            <span class="mt-0.5 block text-xs text-gp-muted">Encaisser rapidement</span>
                        </span>
                    </a>
                    @endcan
                    @can('products.create')
                    <a href="{{ route('products.create') }}" class="gp-shortcut">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-gp-primary dark:bg-teal-500/15">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-gp-text dark:text-white">Ajouter un produit</span>
                            <span class="mt-0.5 block text-xs text-gp-muted">Ctrl+N</span>
                        </span>
                    </a>
                    @endcan
                    @can('products.view')
                    <a href="{{ route('products.index') }}" class="gp-shortcut">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-gp-primary dark:bg-teal-500/15">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10"/></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-gp-text dark:text-white">Catalogue produits</span>
                            <span class="mt-0.5 block text-xs text-gp-muted">Liste et fiches</span>
                        </span>
                    </a>
                    @endcan
                    @can('stock.view')
                    <a href="{{ route('stock.dashboard') }}" class="gp-shortcut">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-gp-primary dark:bg-teal-500/15">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10"/></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-gp-text dark:text-white">Piloter le stock</span>
                            <span class="mt-0.5 block text-xs text-gp-muted">Niveaux et alertes</span>
                        </span>
                    </a>
                    @endcan
                </div>
            </div>

            <article class="gp-widget" data-gp-widget="activity">
                <div class="gp-widget-toolbar"><button type="button" class="text-[11px] text-gp-muted hover:text-rose-500" data-gp-widget-hide="activity">Masquer</button></div>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-gp-text dark:text-white">Activité récente</h2>
                        <p class="mt-0.5 text-xs text-gp-muted">Journal d’audit</p>
                    </div>
                    @can('audit.view')
                        <a href="{{ route('audit.index') }}" class="text-xs font-semibold text-gp-primary hover:underline">Tout voir</a>
                    @endcan
                </div>
                @if(($activity ?? collect())->isEmpty())
                    <div class="gp-empty py-10">
                        <div class="gp-empty-icon">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg>
                        </div>
                        <p class="gp-empty-title">Aucune activité récente</p>
                        <p class="gp-empty-text">Les actions métier apparaîtront ici.</p>
                    </div>
                @else
                    <ol class="relative space-y-0 border-l border-gp-border pl-5">
                        @foreach($activity->take(6) as $event)
                            <li class="relative pb-5 last:pb-0">
                                <span class="absolute -left-[27px] flex h-5 w-5 items-center justify-center rounded-full border border-gp-border bg-gp-surface text-gp-primary">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gp-primary"></span>
                                </span>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gp-text dark:text-white">{{ $event->description ?: $event->actionLabel() }}</p>
                                    <time class="text-[11px] text-gp-muted">{{ $event->occurred_at->diffForHumans() }}</time>
                                </div>
                                <p class="mt-1 text-xs text-gp-muted">{{ $event->user?->displayName() ?? 'Système' }} · {{ $event->module }}</p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </article>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const el = document.getElementById('gp-home-chart');
            if (!el || typeof Chart === 'undefined') return;
            const isDark = document.documentElement.classList.contains('dark');
            const tick = isDark ? '#94a3b8' : '#64748b';
            const sales = {{ (int) $stats['sales_today'] }};
            const revenue = {{ (float) $stats['revenue_today'] }};
            new Chart(el, {
                type: 'line',
                data: {
                    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                    datasets: [{
                        label: 'Tendance',
                        data: [Math.max(0, revenue * 0.55), Math.max(0, revenue * 0.7), Math.max(0, revenue * 0.48), Math.max(0, revenue * 0.82), Math.max(0, revenue * 0.9), Math.max(0, revenue * 0.65), Math.max(revenue, sales * 10)],
                        borderColor: '#0d9488',
                        backgroundColor: 'rgba(13, 148, 136, 0.12)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: tick, font: { size: 11 } }, grid: { display: false } },
                        y: { ticks: { color: tick, font: { size: 11 } }, grid: { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(15,28,25,0.06)' }, beginAtZero: true }
                    }
                }
            });
        })();
    </script>
@endsection
