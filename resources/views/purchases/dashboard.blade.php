@extends('layouts.app')

@section('title', 'Achats')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Dashboard Achats')
@section('subtitle', 'Pilotage des commandes, réceptions et dépenses fournisseurs.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('purchases.create')
            <a href="{{ route('purchases.orders.create') }}" class="gp-btn-primary">Nouveau BC</a>
            <a href="{{ route('purchases.requests.create') }}" class="gp-btn-secondary">Demande d’achat</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('purchases._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="gp-kpi sm:col-span-2 xl:col-span-1 2xl:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Achats du mois</p>
            <p class="mt-2 text-3xl font-bold text-gp-primary">{{ number_format($stats['month_total'], 2, ',', ' ') }} <span class="text-base text-gp-muted">MAD</span></p>
        </article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">En attente</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['pending'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Validées</p><p class="mt-2 text-3xl font-bold text-indigo-600">{{ $stats['confirmed'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Réceptions du jour</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['receipts_today'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Fournisseurs</p><p class="mt-2 text-3xl font-bold">{{ $stats['suppliers'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Dépenses totales</p><p class="mt-2 text-2xl font-bold">{{ number_format($stats['spend_total'], 2, ',', ' ') }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Achats mensuels</h2>
            <canvas id="purchases-monthly-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Répartition par fournisseur</h2>
            <canvas id="purchases-supplier-chart" height="140"></canvas>
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Dernières commandes</h2>
            <a href="{{ route('purchases.orders.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Tout voir</a>
        </div>
        @if($recent->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-lg font-bold">Aucune commande</p>
                <p class="mt-2 text-sm text-gp-muted">Créez votre premier bon de commande fournisseur.</p>
                @can('purchases.create')
                    <a href="{{ route('purchases.orders.create') }}" class="gp-btn-primary mt-5">Créer un BC</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">N°</th>
                            <th class="px-4 py-3">Fournisseur</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Montant</th>
                            <th class="px-4 py-3">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($recent as $order)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('purchases.orders.show', $order) }}" class="hover:text-gp-primary">{{ $order->number }}</a></td>
                                <td class="px-4 py-3">{{ $order->supplier?->name }}</td>
                                <td class="px-4 py-3">{{ $order->store?->name }}</td>
                                <td class="px-4 py-3 font-bold">{{ number_format($order->total_ttc, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3"><span class="gp-badge {{ $order->statusColor() }}">{{ $order->statusLabel() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const monthly = @json($monthly);
        const bySupplier = @json($bySupplier);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const grid = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(15,23,42,0.06)';
        const colors = ['#16a34a','#0ea5e9','#f59e0b','#8b5cf6','#ef4444','#14b8a6'];

        new Chart(document.getElementById('purchases-monthly-chart'), {
            type: 'bar',
            data: { labels: monthly.map(i => i.label), datasets: [{ data: monthly.map(i => i.total), backgroundColor: '#16a34a', borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick }, grid: { display: false } }, y: { ticks: { color: tick }, grid: { color: grid } } } }
        });
        new Chart(document.getElementById('purchases-supplier-chart'), {
            type: 'doughnut',
            data: { labels: bySupplier.map(i => i.name), datasets: [{ data: bySupplier.map(i => i.total), backgroundColor: colors, borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom', labels: { color: tick } } } }
        });
    </script>
@endsection
