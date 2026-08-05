@extends('layouts.app')

@section('title', 'Stock')
@section('breadcrumb', 'Catalogue / Stock')
@section('heading', 'Dashboard Stock')
@section('subtitle', 'Pilotage des niveaux, mouvements et alertes par boutique.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('stock.move')
            <a href="{{ route('stock.movements.create') }}" class="gp-btn-primary">Nouveau mouvement</a>
        @endcan
        @can('stock.inventory')
            <a href="{{ route('stock.inventories.create') }}" class="gp-btn-secondary">Nouvel inventaire</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('stock._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-100">{{ session('success') }}</div>
    @endif

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="gp-kpi sm:col-span-2 xl:col-span-1 2xl:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Valeur du stock</p>
            <p class="mt-2 text-3xl font-bold text-gp-primary">{{ number_format($stats['value'], 2, ',', ' ') }} <span class="text-base font-semibold text-gp-muted">MAD</span></p>
        </article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Disponibles</p><p class="mt-2 text-3xl font-bold">{{ $stats['available'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Faibles</p><p class="mt-2 text-3xl font-bold text-gp-warning">{{ $stats['low'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Ruptures</p><p class="mt-2 text-3xl font-bold text-rose-600">{{ $stats['out'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Entrées du jour</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['in_today'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Sorties du jour</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['out_today'] }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold">Évolution nette (7 jours)</h2>
                <span class="gp-badge">Unités</span>
            </div>
            <canvas id="stock-evolution-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold">Mouvements (7 jours)</h2>
                <span class="gp-badge">Entrées / Sorties</span>
            </div>
            <canvas id="stock-movements-chart" height="140"></canvas>
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Mouvements récents</h2>
            <a href="{{ route('stock.movements.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Tout voir</a>
        </div>
        @if($recent->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-lg font-bold">Aucun mouvement</p>
                <p class="mt-2 text-sm text-gp-muted">Enregistrez une entrée pour démarrer le suivi.</p>
                @can('stock.move')
                    <a href="{{ route('stock.movements.create') }}" class="gp-btn-primary mt-5">Créer un mouvement</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Produit</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Qté</th>
                            <th class="px-4 py-3">Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($recent as $movement)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $movement->moved_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('stock.movements.show', $movement) }}" class="hover:text-gp-primary">{{ $movement->product?->name }}</a></td>
                                <td class="px-4 py-3">{{ $movement->store?->name }}</td>
                                <td class="px-4 py-3"><span class="gp-badge">{{ $movement->typeLabel() }}</span></td>
                                <td class="px-4 py-3 font-mono text-xs">{{ number_format($movement->quantity, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-gp-muted">{{ $movement->user?->name ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const evolution = @json($evolution);
        const movements = @json($movementsChart);
        const isDark = document.documentElement.classList.contains('dark');
        const grid = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(15,23,42,0.06)';
        const tick = isDark ? '#94a3b8' : '#64748b';

        new Chart(document.getElementById('stock-evolution-chart'), {
            type: 'line',
            data: {
                labels: evolution.map(i => i.label),
                datasets: [{
                    label: 'Net',
                    data: evolution.map(i => i.net),
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,0.12)',
                    fill: true,
                    tension: 0.35,
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick }, grid: { color: grid } }, y: { ticks: { color: tick }, grid: { color: grid } } } }
        });

        new Chart(document.getElementById('stock-movements-chart'), {
            type: 'bar',
            data: {
                labels: movements.map(i => i.label),
                datasets: [
                    { label: 'Entrées', data: movements.map(i => i.in), backgroundColor: '#16a34a', borderRadius: 8 },
                    { label: 'Sorties', data: movements.map(i => i.out), backgroundColor: '#0ea5e9', borderRadius: 8 },
                ]
            },
            options: { plugins: { legend: { labels: { color: tick } } }, scales: { x: { ticks: { color: tick }, grid: { display: false } }, y: { ticks: { color: tick }, grid: { color: grid } } } }
        });
    </script>
@endsection
