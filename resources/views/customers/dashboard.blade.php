@extends('layouts.app')

@section('title', 'Clients')
@section('breadcrumb', 'Relation Client')
@section('heading', 'Dashboard Clients')
@section('subtitle', 'Référentiel clients, activité et préparation commerciale.')

@section('actions')
    @can('customers.create')
        <a href="{{ route('customers.create') }}" class="gp-btn-primary">Nouveau client</a>
    @endcan
@endsection

@section('content')
    @include('customers._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Total</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Nouveaux (30j)</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['new'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Actifs</p><p class="mt-2 text-3xl font-bold text-gp-primary">{{ $stats['active'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Inactifs</p><p class="mt-2 text-3xl font-bold text-gp-muted">{{ $stats['inactive'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">CA généré</p><p class="mt-2 text-2xl font-bold">{{ number_format($stats['revenue'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Solde clients</p><p class="mt-2 text-2xl font-bold {{ $stats['balance'] > 0 ? 'text-amber-600' : '' }}">{{ number_format($stats['balance'], 2, ',', ' ') }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Évolution des nouveaux clients</h2>
            <canvas id="customers-evolution-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Top clients (CA)</h2>
            <canvas id="customers-top-chart" height="140"></canvas>
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Activité récente</h2>
            <a href="{{ route('customers.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Voir la liste</a>
        </div>
        @if($recent->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-lg font-bold">Aucun client</p>
                <p class="mt-2 text-sm text-gp-muted">Ajoutez votre premier client pour préparer POS et facturation.</p>
                @can('customers.create')
                    <a href="{{ route('customers.create') }}" class="gp-btn-primary mt-5">Créer un client</a>
                @endcan
            </div>
        @else
            <div class="divide-y divide-gp-border dark:divide-white/10">
                @foreach($recent as $customer)
                    <a href="{{ route('customers.show', $customer) }}" class="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50/80 dark:hover:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gp-primary-soft text-sm font-bold text-gp-primary">{{ $customer->initials() }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-gp-text dark:text-white">{{ $customer->displayName() }}</span>
                            <span class="block truncate text-xs text-gp-muted">{{ $customer->code }} · {{ $customer->typeLabel() }} · {{ $customer->city ?: '—' }}</span>
                        </span>
                        <span class="gp-badge {{ $customer->statusColor() }}">{{ $customer->statusLabel() }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const evolution = @json($evolution);
        const top = @json($top->map(fn ($c) => ['name' => $c->displayName(), 'total' => (float) $c->lifetime_revenue]));
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#0ea5e9','#f59e0b','#8b5cf6','#ef4444','#14b8a6'];

        new Chart(document.getElementById('customers-evolution-chart'), {
            type: 'line',
            data: { labels: evolution.map(i => i.label), datasets: [{ data: evolution.map(i => i.count), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, precision: 0 } } }
        });
        new Chart(document.getElementById('customers-top-chart'), {
            type: 'bar',
            data: { labels: top.map(i => i.name), datasets: [{ data: top.map(i => i.total), backgroundColor: colors, borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
    </script>
@endsection
