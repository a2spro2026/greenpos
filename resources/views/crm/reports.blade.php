@extends('layouts.app')
@section('title', 'Rapports CRM')
@section('breadcrumb', 'CRM / Rapports')
@section('heading', 'Rapports commerciaux')
@section('content')
@include('crm._nav')
<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="gp-kpi"><p class="text-xs uppercase text-gp-muted">CA gagné</p><p class="mt-2 text-2xl font-bold">{{ number_format($stats['revenue_won'], 0, ',', ' ') }}</p></article>
    <article class="gp-kpi"><p class="text-xs uppercase text-gp-muted">CA mois</p><p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($stats['revenue_month'], 0, ',', ' ') }}</p></article>
    <article class="gp-kpi"><p class="text-xs uppercase text-gp-muted">Leads</p><p class="mt-2 text-3xl font-bold">{{ $stats['funnel']['leads'] }}</p></article>
    <article class="gp-kpi"><p class="text-xs uppercase text-gp-muted">Gagnés / Perdus</p><p class="mt-2 text-2xl font-bold">{{ $stats['funnel']['won'] }} / {{ $stats['funnel']['lost'] }}</p></article>
</section>
<section class="mb-6 grid gap-4 xl:grid-cols-2">
    <article class="gp-card"><h2 class="mb-4 text-sm font-bold">Entonnoir de conversion</h2><canvas id="crm-funnel-chart" height="160"></canvas></article>
    <article class="gp-card overflow-hidden p-0">
        <div class="border-b border-gp-border px-5 py-4"><h2 class="text-sm font-bold">Performance commerciaux</h2></div>
        <table class="gp-table">
            <thead><tr><th>Commercial</th><th>Ouvertes</th><th>Gagnées</th><th>CA gagné</th></tr></thead>
            <tbody>
            @forelse($stats['by_owner'] as $row)
                <tr>
                    <td>{{ $row['user'] }}</td>
                    <td>{{ $row['open_count'] }}</td>
                    <td>{{ $row['won_count'] }}</td>
                    <td class="font-semibold">{{ number_format($row['won_amount'], 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-10 text-center text-gp-muted">Pas de données</td></tr>
            @endforelse
            </tbody>
        </table>
    </article>
</section>
<article class="gp-card"><h2 class="mb-4 text-sm font-bold">Pipeline (valeur)</h2><canvas id="crm-pipe-chart" height="120"></canvas></article>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const funnel = @json($stats['funnel']);
const pipe = @json(collect($stats['pipeline'])->map(fn($c)=>['label'=>$c['label'],'amount'=>$c['amount']]));
const tick = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
new Chart(document.getElementById('crm-funnel-chart'), {
    type: 'bar',
    data: { labels: ['Leads','Qualifiés','Opportunités','Gagnés','Perdus'], datasets: [{ data: [funnel.leads, funnel.qualified, funnel.opportunities, funnel.won, funnel.lost], backgroundColor: ['#0ea5e9','#8b5cf6','#f59e0b','#10b981','#f43f5e'], borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, beginAtZero: true } } }
});
new Chart(document.getElementById('crm-pipe-chart'), {
    type: 'bar',
    data: { labels: pipe.map(p => p.label), datasets: [{ data: pipe.map(p => p.amount), backgroundColor: 'rgba(20,184,166,.55)', borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick, maxRotation: 45 } }, y: { ticks: { color: tick }, beginAtZero: true } } }
});
</script>
@endsection
