@extends('layouts.app')

@section('title', 'POS')
@section('breadcrumb', 'Ventes / POS')
@section('heading', 'Dashboard POS')
@section('subtitle', 'Pilotage des ventes caisse du jour.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('pos.open')
            <a href="{{ route('pos.sessions.create') }}" class="gp-btn-secondary">Ouvrir caisse</a>
        @endcan
        @can('pos.sell')
            <a href="{{ route('pos.terminal') }}" class="gp-btn-primary">Ouvrir le terminal</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('pos._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Ventes du jour</p><p class="mt-2 text-3xl font-bold text-gp-primary">{{ number_format($stats['sales_total'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Tickets</p><p class="mt-2 text-3xl font-bold">{{ $stats['tickets'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Panier moyen</p><p class="mt-2 text-3xl font-bold">{{ number_format($stats['avg_ticket'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Caisse</p><p class="mt-2 text-xl font-bold">{{ $stats['open_session'] ?: 'Fermée' }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Ventes horaires</h2>
            <canvas id="pos-hourly-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Modes de paiement</h2>
            <canvas id="pos-payments-chart" height="140"></canvas>
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Produits les plus vendus</h2></div>
            @if($topProducts->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune vente aujourd’hui.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($topProducts as $row)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <span class="font-semibold">{{ $row->product_name }}</span>
                            <span class="text-gp-muted">{{ number_format($row->qty, 0, ',', ' ') }} · {{ number_format($row->total, 2, ',', ' ') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
        <article class="gp-card">
            <h2 class="mb-3 text-sm font-bold">Meilleur vendeur</h2>
            @if($topCashier)
                <p class="text-2xl font-bold">{{ $topCashier->cashier?->name ?: 'Caissier' }}</p>
                <p class="mt-2 text-sm text-gp-muted">{{ $topCashier->tickets }} tickets · {{ number_format($topCashier->total, 2, ',', ' ') }} MAD</p>
            @else
                <p class="text-sm text-gp-muted">Pas encore de ventes aujourd’hui.</p>
            @endif
            <h2 class="mb-3 mt-6 text-sm font-bold">Tickets récents</h2>
            <ul class="space-y-2 text-sm">
                @forelse($recent as $sale)
                    <li><a href="{{ route('pos.tickets.show', $sale) }}" class="font-semibold text-gp-primary hover:underline">{{ $sale->number }}</a> <span class="text-gp-muted">· {{ number_format($sale->total_ttc, 2, ',', ' ') }}</span></li>
                @empty
                    <li class="text-gp-muted">Aucun ticket.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const hourly = @json($hourly);
        const paymentMix = @json($paymentMix);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#0ea5e9','#f59e0b','#8b5cf6'];

        new Chart(document.getElementById('pos-hourly-chart'), {
            type: 'bar',
            data: { labels: hourly.map(i => i.label), datasets: [{ data: hourly.map(i => i.total), backgroundColor: '#16a34a', borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
        new Chart(document.getElementById('pos-payments-chart'), {
            type: 'doughnut',
            data: {
                labels: paymentMix.length ? paymentMix.map(i => i.method) : ['Aucune vente'],
                datasets: [{ data: paymentMix.length ? paymentMix.map(i => i.total) : [1], backgroundColor: paymentMix.length ? colors : ['#334155'], borderWidth: 0 }]
            },
            options: { plugins: { legend: { position: 'bottom', labels: { color: tick } } } }
        });
    </script>
@endsection
