@extends('layouts.app')

@section('title', 'Multi-boutiques')
@section('breadcrumb', 'Administration / Boutiques')
@section('heading', 'Multi-boutiques')
@section('subtitle', 'Pilotage comparatif de vos points de vente.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('stores.export')
            <a href="{{ route('stores.export') }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('stores.create')
            <a href="{{ route('stores.create') }}" class="gp-btn-primary">Nouvelle boutique</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('stores._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Boutiques</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Actives</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['active'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Inactives</p><p class="mt-2 text-3xl font-bold text-slate-500">{{ $stats['inactive'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">CA cumulé</p><p class="mt-2 text-2xl font-bold">{{ number_format($stats['revenue_total'], 0, ',', ' ') }} <span class="text-sm text-gp-muted">MAD</span></p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Ventes du jour</p><p class="mt-2 text-2xl font-bold text-sky-600">{{ number_format($stats['sales_today_total'], 0, ',', ' ') }} <span class="text-sm text-gp-muted">MAD</span></p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Chiffre d'affaires par boutique</h2>
            <canvas id="stores-revenue-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Ventes du jour · comparatif</h2>
            <canvas id="stores-today-chart" height="140"></canvas>
        </article>
    </section>

    <section class="mb-6">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Stock par boutique (quantités)</h2>
            <canvas id="stores-stock-chart" height="100"></canvas>
        </article>
    </section>

    <section class="mb-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold">Cartes boutiques</h2>
            <span class="text-xs text-gp-muted">Carte géographique · préparée</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($stats['cards'] as $card)
                @php $store = $card['store']; @endphp
                <article class="gp-card group transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($store->logoUrl())
                                <img src="{{ $store->logoUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover ring-1 ring-gp-border dark:ring-white/10">
                            @else
                                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gp-primary-soft text-sm font-bold text-gp-primary">{{ $store->initials() }}</span>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ route('stores.show', $store) }}" class="block truncate font-bold hover:text-gp-primary">{{ $store->name }}</a>
                                <p class="text-xs text-gp-muted">{{ $store->city ?: '—' }} · {{ $store->code ?: 'sans code' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ $store->statusColor() }}">{{ $store->statusLabel() }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-gp-bg px-2 py-2 dark:bg-white/5">
                            <p class="text-[10px] uppercase text-gp-muted">CA</p>
                            <p class="text-sm font-bold">{{ number_format($card['revenue'], 0, ',', ' ') }}</p>
                        </div>
                        <div class="rounded-xl bg-gp-bg px-2 py-2 dark:bg-white/5">
                            <p class="text-[10px] uppercase text-gp-muted">Aujourd'hui</p>
                            <p class="text-sm font-bold">{{ number_format($card['sales_today_total'], 0, ',', ' ') }}</p>
                        </div>
                        <div class="rounded-xl bg-gp-bg px-2 py-2 dark:bg-white/5">
                            <p class="text-[10px] uppercase text-gp-muted">Stock</p>
                            <p class="text-sm font-bold">{{ number_format($card['stock_qty'], 0, ',', ' ') }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-2 border-t border-gp-border pt-3 dark:border-white/10">
                        <span class="text-xs text-gp-muted">{{ $card['users_count'] }} utilisateur(s)</span>
                        <form method="POST" action="{{ route('stores.switch', $store) }}">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-gp-primary hover:underline">Activer</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="gp-card border-dashed">
        <div class="flex flex-col items-center justify-center gap-2 py-10 text-center">
            <p class="text-sm font-bold">Vue cartographique</p>
            <p class="max-w-md text-xs text-gp-muted">Les coordonnées GPS des boutiques sont prêtes. L'affichage carte sera branché dans une prochaine itération.</p>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const labels = @json($stats['chart_labels']);
        const revenue = @json($stats['chart_revenue']);
        const today = @json($stats['chart_sales_today']);
        const stock = @json($stats['chart_stock']);
        const teal = '#0d9488';
        const sky = '#0284c7';
        const amber = '#d97706';
        new Chart(document.getElementById('stores-revenue-chart'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'CA (MAD)', data: revenue, backgroundColor: teal, borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
        new Chart(document.getElementById('stores-today-chart'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Ventes jour', data: today, backgroundColor: sky, borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
        new Chart(document.getElementById('stores-stock-chart'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Stock', data: stock, borderColor: amber, backgroundColor: 'rgba(217,119,6,.15)', fill: true, tension: .35 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    </script>
@endsection
