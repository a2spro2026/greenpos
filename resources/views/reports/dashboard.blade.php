@extends('layouts.app')

@section('title', 'Rapports & BI')
@section('breadcrumb', 'Pilotage / Rapports')
@section('heading', 'Tableau de bord BI')
@section('subtitle', 'Vue consolidée de l\'activité commerciale.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('reports.export')
            <a href="{{ route('reports.export', array_merge(request()->only(['from','to','store_id','user_id','category_id','product_id','customer_id']), ['type' => 'sales'])) }}" class="gp-btn-secondary">Exporter CSV</a>
        @endcan
        @can('reports.print')
            <a href="{{ route('reports.print', request()->only(['from','to','store_id','user_id','category_id','product_id','customer_id'])) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('reports._nav')
    @include('reports._filters', ['action' => route('reports.dashboard')])

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8">
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Chiffre d'affaires</p>
            <p class="mt-2 text-xl font-bold text-gp-primary">{{ number_format($revenue, 2, ',', ' ') }} <span class="text-sm font-normal text-gp-muted">MAD</span></p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Ventes</p>
            <p class="mt-2 text-3xl font-bold">{{ $count }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Ticket moyen</p>
            <p class="mt-2 text-xl font-bold">{{ number_format($avgTicket, 2, ',', ' ') }}</p>
        </article>
        @can('reports.financial')
            <article class="gp-kpi">
                <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Marge estimée</p>
                <p class="mt-2 text-xl font-bold text-emerald-600">{{ number_format($margin, 2, ',', ' ') }}</p>
            </article>
        @endcan
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Clients actifs</p>
            <p class="mt-2 text-3xl font-bold">{{ $activeCustomers }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Produits vendus</p>
            <p class="mt-2 text-3xl font-bold">{{ number_format($productsSold) }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Stock critique</p>
            <p class="mt-2 text-3xl font-bold text-orange-600">{{ $criticalStock->count() }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Croissance</p>
            <p class="mt-2 text-xl font-bold {{ $growth >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $growth >= 0 ? '+' : '' }}{{ $growth }}%</p>
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Évolution mensuelle</h2>
            <canvas id="chart-monthly" height="160"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Évolution quotidienne (7 jours)</h2>
            <canvas id="chart-daily" height="160"></canvas>
        </article>
    </section>

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
                                <p class="font-semibold">{{ $p['product_name'] }}</p>
                                <p class="text-xs text-gp-muted">{{ number_format($p['qty']) }} vendus</p>
                            </div>
                            <span class="font-bold">{{ number_format($p['total'], 2, ',', ' ') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Stock critique</h2></div>
            @if($criticalStock->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune alerte.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($criticalStock as $l)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <p class="font-semibold">{{ $l->product?->name }}</p>
                                <p class="text-xs text-gp-muted">{{ $l->store?->name }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $l->stockStatus() === 'out' ? 'bg-rose-100 text-rose-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ $l->quantity }} / min {{ $l->min_quantity }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const opts = { plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ color:tick }}, y:{ ticks:{ color:tick }}} };

        const monthly = @json($monthly);
        new Chart(document.getElementById('chart-monthly'), {
            type:'line',
            data:{ labels:monthly.map(m=>m.label), datasets:[{ data:monthly.map(m=>m.total), borderColor:'#16a34a', backgroundColor:'rgba(22,163,74,.12)', fill:true, tension:.35, pointRadius:3 }] },
            options:opts
        });

        const daily = @json($daily);
        new Chart(document.getElementById('chart-daily'), {
            type:'bar',
            data:{ labels:daily.map(d=>d.label), datasets:[{ data:daily.map(d=>d.total), backgroundColor:'rgba(59,130,246,.65)', borderRadius:4 }] },
            options:opts
        });
    </script>
@endsection
