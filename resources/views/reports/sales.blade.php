@extends('layouts.app')

@section('title', 'Rapport Ventes')
@section('breadcrumb', 'Rapports / Ventes')
@section('heading', 'Rapport des ventes')
@section('subtitle', 'Analyse détaillée des ventes par période, boutique et commercial.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('reports.export')
            <a href="{{ route('reports.export', array_merge(request()->only(['from','to','store_id','user_id','category_id','product_id','customer_id']), ['type' => 'sales'])) }}" class="gp-btn-secondary">Exporter CSV</a>
        @endcan
        @can('reports.print')
            <a href="{{ route('reports.print', array_merge(request()->only(['from','to','store_id','user_id','category_id','product_id','customer_id']), ['type' => 'sales'])) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('reports._nav')
    @include('reports._filters', ['action' => route('reports.sales')])

    <section class="mb-6 grid gap-4 sm:grid-cols-3">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">CA total</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($totalRevenue, 2, ',', ' ') }} MAD</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Nombre de ventes</p><p class="mt-2 text-3xl font-bold">{{ $totalCount }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Ticket moyen</p><p class="mt-2 text-2xl font-bold">{{ $totalCount > 0 ? number_format($totalRevenue / $totalCount, 2, ',', ' ') : '0,00' }} MAD</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-3">
        <article class="gp-card xl:col-span-2">
            <h2 class="mb-4 text-sm font-bold">Ventes par jour</h2>
            <canvas id="chart-by-day" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Par boutique</h2>
            <canvas id="chart-by-store" height="140"></canvas>
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Ventes par mois</h2>
            <canvas id="chart-by-month" height="120"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Par commercial</h2>
            <canvas id="chart-by-salesperson" height="120"></canvas>
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Détail des ventes</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr><th class="px-4 py-3">Réf</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Boutique</th><th class="px-4 py-3">Origine</th><th class="px-4 py-3 text-right">Total TTC</th></tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($sales as $s)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('sales.show', $s) }}" class="text-gp-primary hover:underline">{{ $s->number }}</a></td>
                            <td class="px-4 py-3 text-gp-muted">{{ optional($s->sold_at)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $s->customer?->name ?? 'Passage' }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $s->store?->name }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold dark:bg-white/10">{{ $s->originLabel() }}</span></td>
                            <td class="px-4 py-3 text-right font-bold">{{ number_format($s->total_ttc, 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                    @foreach($posOnly as $s)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('pos.tickets.show', $s) }}" class="text-gp-primary hover:underline">{{ $s->number }}</a></td>
                            <td class="px-4 py-3 text-gp-muted">{{ optional($s->completed_at)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $s->customer?->name ?? 'Passage' }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $s->store?->name }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800">POS</span></td>
                            <td class="px-4 py-3 text-right font-bold">{{ number_format($s->total_ttc, 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                    @if($sales->isEmpty() && $posOnly->isEmpty())
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gp-muted">Aucune vente sur la période.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const tick = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
        const opts = { plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ color:tick }}, y:{ ticks:{ color:tick }}} };
        const colors = ['#16a34a','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#06b6d4'];

        const byDay = @json($byDay);
        new Chart(document.getElementById('chart-by-day'), { type:'bar', data:{ labels:byDay.map(d=>d.label), datasets:[{ data:byDay.map(d=>d.total), backgroundColor:'rgba(22,163,74,.65)', borderRadius:4 }] }, options:opts });

        const byMonth = @json($byMonth);
        new Chart(document.getElementById('chart-by-month'), { type:'line', data:{ labels:byMonth.map(m=>m.label), datasets:[{ data:byMonth.map(m=>m.total), borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.12)', fill:true, tension:.35 }] }, options:opts });

        const byStore = @json($byStore);
        new Chart(document.getElementById('chart-by-store'), { type:'doughnut', data:{ labels:byStore.map(s=>s.name), datasets:[{ data:byStore.map(s=>s.total), backgroundColor:colors }] }, options:{ plugins:{ legend:{ position:'bottom', labels:{ color:tick, boxWidth:12 } } } } });

        const bySp = @json($bySalesperson);
        new Chart(document.getElementById('chart-by-salesperson'), { type:'bar', data:{ labels:bySp.map(s=>s.name), datasets:[{ data:bySp.map(s=>s.total), backgroundColor:colors }] }, options:{ ...opts, indexAxis:'y' } });
    </script>
@endsection
