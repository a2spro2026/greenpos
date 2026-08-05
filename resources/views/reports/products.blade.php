@extends('layouts.app')

@section('title', 'Rapport Produits')
@section('breadcrumb', 'Rapports / Produits')
@section('heading', 'Rapport Produits')
@section('subtitle', 'Performance produits, rotation et produits sans mouvement.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('reports.export')
            <a href="{{ route('reports.export', array_merge(request()->only(['from','to','store_id','category_id','product_id']), ['type' => 'products'])) }}" class="gp-btn-secondary">Exporter CSV</a>
        @endcan
        @can('reports.print')
            <a href="{{ route('reports.print', array_merge(request()->only(['from','to','store_id','category_id','product_id']), ['type' => 'products'])) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('reports._nav')
    @include('reports._filters', ['action' => route('reports.products')])

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Top produits (CA)</h2>
            <canvas id="chart-top" height="180"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Ventes par catégorie</h2>
            <canvas id="chart-category" height="180"></canvas>
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Top produits</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr><th class="px-4 py-3 text-left">Produit</th><th class="px-4 py-3 text-right">Qté</th><th class="px-4 py-3 text-right">CA</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @forelse($topProducts as $p)
                            <tr><td class="px-4 py-3 font-semibold">{{ $p['product_name'] }}</td><td class="px-4 py-3 text-right">{{ number_format($p['qty']) }}</td><td class="px-4 py-3 text-right font-bold">{{ number_format($p['total'], 2, ',', ' ') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gp-muted">Aucune donnée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Rotation du stock</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr><th class="px-4 py-3 text-left">Produit</th><th class="px-4 py-3 text-right">Vendus</th><th class="px-4 py-3 text-right">Stock</th><th class="px-4 py-3 text-right">Rotation</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @forelse($rotation as $r)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $r['product_name'] }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($r['qty']) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($r['stock']) }}</td>
                                <td class="px-4 py-3 text-right font-bold {{ $r['rotation'] >= 1 ? 'text-emerald-600' : 'text-orange-600' }}">{{ $r['rotation'] }}x</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gp-muted">Aucune donnée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Produits peu vendus</h2></div>
            @if($slowProducts->isEmpty())
                <div class="px-6 py-8 text-center text-sm text-gp-muted">Tous les produits ont des ventes.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($slowProducts as $p)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div><p class="font-semibold">{{ $p->name }}</p><p class="text-xs text-gp-muted">{{ $p->sku }}</p></div>
                            <span class="text-gp-muted">{{ number_format($p->sale_price, 2, ',', ' ') }} MAD</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Sans mouvement</h2></div>
            @if($noMovement->isEmpty())
                <div class="px-6 py-8 text-center text-sm text-gp-muted">Tous les produits ont eu des mouvements.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($noMovement as $p)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div><p class="font-semibold">{{ $p->name }}</p><p class="text-xs text-gp-muted">{{ $p->category?->name ?? '—' }}</p></div>
                            <span class="text-orange-600 text-xs font-semibold">Inactif</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const tick = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#ef4444','#14b8a6'];
        const top = @json($topProducts->take(8));
        new Chart(document.getElementById('chart-top'), { type:'bar', data:{ labels:top.map(p=>p.product_name), datasets:[{ data:top.map(p=>p.total), backgroundColor:colors }] }, options:{ plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ color:tick, maxRotation:45 }}, y:{ ticks:{ color:tick }}} } });
        const cats = @json($byCategory);
        new Chart(document.getElementById('chart-category'), { type:'pie', data:{ labels:cats.map(c=>c.name), datasets:[{ data:cats.map(c=>c.total), backgroundColor:colors }] }, options:{ plugins:{ legend:{ position:'bottom', labels:{ color:tick, boxWidth:12 } } } } });
    </script>
@endsection
