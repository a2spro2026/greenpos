@extends('layouts.app')

@section('title', 'Rapport Paiements')
@section('breadcrumb', 'Rapports / Paiements')
@section('heading', 'Rapport Paiements')
@section('subtitle', 'Analyse des encaissements, modes de paiement et remboursements.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('reports.export')
            <a href="{{ route('reports.export', array_merge(request()->only(['from','to','store_id','customer_id']), ['type' => 'payments'])) }}" class="gp-btn-secondary">Exporter CSV</a>
        @endcan
        @can('reports.print')
            <a href="{{ route('reports.print', array_merge(request()->only(['from','to','store_id','customer_id']), ['type' => 'payments'])) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('reports._nav')
    @include('reports._filters', ['action' => route('reports.payments')])

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Encaissé</p><p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($validatedTotal, 2, ',', ' ') }} MAD</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">En attente</p><p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($pendingInvoices, 2, ',', ' ') }} MAD</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Remboursements</p><p class="mt-2 text-2xl font-bold text-orange-600">{{ number_format($refunds, 2, ',', ' ') }} MAD</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Modes utilisés</p><p class="mt-2 text-3xl font-bold">{{ $byMethod->count() }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Paiements par mode</h2>
            <canvas id="chart-methods" height="180"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Évolution mensuelle</h2>
            <canvas id="chart-monthly" height="180"></canvas>
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Détail par mode de paiement</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr><th class="px-4 py-3 text-left">Mode</th><th class="px-4 py-3 text-right">Transactions</th><th class="px-4 py-3 text-right">Montant</th><th class="px-4 py-3 text-right">Part</th></tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($byMethod as $m)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $m['label'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $m['count'] }}</td>
                            <td class="px-4 py-3 text-right font-bold">{{ number_format($m['total'], 2, ',', ' ') }} MAD</td>
                            <td class="px-4 py-3 text-right text-gp-muted">{{ $validatedTotal > 0 ? round(($m['total'] / $validatedTotal) * 100, 1) : 0 }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gp-muted">Aucun paiement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const tick = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#06b6d4'];
        const methods = @json($byMethod);
        new Chart(document.getElementById('chart-methods'), { type:'doughnut', data:{ labels:methods.map(m=>m.label), datasets:[{ data:methods.map(m=>m.total), backgroundColor:colors }] }, options:{ plugins:{ legend:{ position:'bottom', labels:{ color:tick, boxWidth:12 } } } } });
        const monthly = @json($monthly);
        new Chart(document.getElementById('chart-monthly'), { type:'line', data:{ labels:monthly.map(m=>m.label), datasets:[{ data:monthly.map(m=>m.total), borderColor:'#16a34a', backgroundColor:'rgba(22,163,74,.12)', fill:true, tension:.35 }] }, options:{ plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ color:tick }}, y:{ ticks:{ color:tick }}} } });
    </script>
@endsection
