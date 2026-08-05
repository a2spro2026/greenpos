@extends('layouts.app')

@section('title', 'Ventes')
@section('breadcrumb', 'Ventes / Dashboard')
@section('heading', 'Dashboard Ventes')
@section('subtitle', 'Suivi des performances commerciales et des ventes.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('sales.export')
            <a href="{{ route('sales.export') }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('sales.create')
            <a href="{{ route('sales.create') }}" class="gp-btn-primary">Nouvelle vente</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('sales._nav')

    {{-- KPIs --}}
    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Chiffre d'affaires</p>
            <p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($stats['revenue'], 2, ',', ' ') }} <span class="text-base font-normal text-gp-muted">MAD</span></p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Nombre de ventes</p>
            <p class="mt-2 text-3xl font-bold">{{ $stats['count'] }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Ticket moyen</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($stats['avg_ticket'], 2, ',', ' ') }} <span class="text-base font-normal text-gp-muted">MAD</span></p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Produits vendus</p>
            <p class="mt-2 text-3xl font-bold">{{ number_format($stats['products_sold']) }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Clients actifs</p>
            <p class="mt-2 text-3xl font-bold">{{ $stats['active_customers'] }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Croissance</p>
            <p class="mt-2 text-2xl font-bold {{ $stats['growth'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ $stats['growth'] >= 0 ? '+' : '' }}{{ $stats['growth'] }}%
            </p>
        </article>
    </section>

    {{-- Charts --}}
    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Évolution quotidienne</h2>
            <canvas id="daily-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Évolution mensuelle</h2>
            <canvas id="monthly-chart" height="140"></canvas>
        </article>
    </section>

    {{-- Top products + Top customers --}}
    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Top produits</h2></div>
            @if($topProducts->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune donnée.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($topProducts as $p)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <p class="font-semibold">{{ $p->product_name }}</p>
                                <p class="text-xs text-gp-muted">{{ number_format($p->qty) }} vendus</p>
                            </div>
                            <span class="font-bold">{{ number_format($p->total, 2, ',', ' ') }} MAD</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Top clients</h2></div>
            @if($topCustomers->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune donnée.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($topCustomers as $c)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <p class="font-semibold">{{ $c->customer?->name ?? 'Client' }}</p>
                                <p class="text-xs text-gp-muted">{{ $c->cnt }} ventes</p>
                            </div>
                            <span class="font-bold">{{ number_format($c->total, 2, ',', ' ') }} MAD</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    {{-- Recent sales --}}
    <section class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Dernières ventes</h2>
            <a href="{{ route('sales.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Tout voir</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3">Réf</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Origine</th>
                        <th class="px-4 py-3">Total TTC</th>
                        <th class="px-4 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($recent as $s)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('sales.show', $s) }}" class="text-gp-primary hover:underline">{{ $s->number }}</a></td>
                            <td class="px-4 py-3">{{ $s->customer?->name ?? 'Passage' }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ optional($s->sold_at)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold dark:bg-white/10">{{ $s->originLabel() }}</span></td>
                            <td class="px-4 py-3 font-bold">{{ number_format($s->total_ttc, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $s->statusColor() }}">{{ $s->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gp-muted">Aucune vente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const opts = { plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ color:tick }}, y:{ ticks:{ color:tick }}} };

        const daily = @json($daily);
        new Chart(document.getElementById('daily-chart'), {
            type:'bar',
            data:{ labels:daily.map(d=>d.label), datasets:[{ data:daily.map(d=>d.total), backgroundColor:'rgba(22,163,74,.65)', borderRadius:4 }] },
            options:opts
        });

        const monthly = @json($monthly);
        new Chart(document.getElementById('monthly-chart'), {
            type:'line',
            data:{ labels:monthly.map(m=>m.label), datasets:[{ data:monthly.map(m=>m.total), borderColor:'#16a34a', backgroundColor:'rgba(22,163,74,.12)', fill:true, tension:.35, pointRadius:4 }] },
            options:opts
        });
    </script>
@endsection
