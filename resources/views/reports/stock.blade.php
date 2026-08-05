@extends('layouts.app')

@section('title', 'Rapport Stock')
@section('breadcrumb', 'Rapports / Stock')
@section('heading', 'Rapport Stock')
@section('subtitle', 'Mouvements, inventaires, alertes et valorisation du stock.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('reports.export')
            <a href="{{ route('reports.export', array_merge(request()->only(['from','to','store_id','product_id']), ['type' => 'stock'])) }}" class="gp-btn-secondary">Exporter CSV</a>
        @endcan
        @can('reports.print')
            <a href="{{ route('reports.print', array_merge(request()->only(['from','to','store_id','product_id']), ['type' => 'stock'])) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('reports._nav')
    @include('reports._filters', ['action' => route('reports.stock')])

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Valorisation</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($valuation, 2, ',', ' ') }} MAD</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Entrées</p><p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($entries->sum('quantity')) }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Sorties</p><p class="mt-2 text-2xl font-bold text-rose-600">{{ number_format($exits->sum('quantity')) }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Sous seuil</p><p class="mt-2 text-2xl font-bold text-orange-600">{{ $belowThreshold->count() }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Entrées / Sorties mensuelles</h2>
            <canvas id="chart-movements" height="180"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Valorisation par catégorie</h2>
            <canvas id="chart-valuation" height="180"></canvas>
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Produits sous seuil</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr><th class="px-4 py-3 text-left">Produit</th><th class="px-4 py-3">Boutique</th><th class="px-4 py-3 text-right">Qté</th><th class="px-4 py-3 text-right">Min</th><th class="px-4 py-3">Statut</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @forelse($belowThreshold->take(15) as $l)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $l->product?->name }}</td>
                                <td class="px-4 py-3 text-gp-muted">{{ $l->store?->name }}</td>
                                <td class="px-4 py-3 text-right">{{ $l->quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ $l->min_quantity }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $l->stockStatus() === 'out' ? 'bg-rose-100 text-rose-800' : 'bg-orange-100 text-orange-800' }}">{{ $l->statusLabel() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gp-muted">Aucune alerte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Inventaires récents</h2></div>
            @if($inventories->isEmpty())
                <div class="px-6 py-8 text-center text-sm text-gp-muted">Aucun inventaire sur la période.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($inventories as $inv)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <a href="{{ route('stock.inventories.show', $inv) }}" class="font-semibold text-gp-primary hover:underline">{{ $inv->name ?? 'INV-'.$inv->id }}</a>
                                <p class="text-xs text-gp-muted">{{ $inv->store?->name }}</p>
                            </div>
                            <span class="text-xs text-gp-muted">{{ $inv->created_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const tick = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
        const monthly = @json($monthlyMovements);
        new Chart(document.getElementById('chart-movements'), {
            type:'bar',
            data:{
                labels:monthly.map(m=>m.label),
                datasets:[
                    { label:'Entrées', data:monthly.map(m=>m.in), backgroundColor:'rgba(22,163,74,.65)', borderRadius:4 },
                    { label:'Sorties', data:monthly.map(m=>m.out), backgroundColor:'rgba(239,68,68,.65)', borderRadius:4 }
                ]
            },
            options:{ plugins:{ legend:{ labels:{ color:tick } } }, scales:{ x:{ ticks:{ color:tick }}, y:{ ticks:{ color:tick }}} }
        });
        const cats = @json($byCategory->take(8));
        const colors = ['#16a34a','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#ef4444','#14b8a6'];
        new Chart(document.getElementById('chart-valuation'), { type:'pie', data:{ labels:cats.map(c=>c.name), datasets:[{ data:cats.map(c=>c.value), backgroundColor:colors }] }, options:{ plugins:{ legend:{ position:'bottom', labels:{ color:tick, boxWidth:12 } } } } });
    </script>
@endsection
