@extends('layouts.app')
@section('title', 'CRM')
@section('breadcrumb', 'CRM Enterprise')
@section('heading', 'Dashboard CRM')
@section('subtitle', 'Pipeline, leads et performance commerciale.')
@section('actions')
    <a href="{{ route('crm.leads.create') }}" class="gp-btn-secondary">Nouveau lead</a>
    <a href="{{ route('crm.pipeline') }}" class="gp-btn-primary">Ouvrir le pipeline</a>
@endsection
@section('content')
@include('crm._nav')

<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
    <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Prospects</p><p class="mt-2 text-3xl font-bold">{{ $stats['prospects'] }}</p></article>
    <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Leads actifs</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['active_leads'] }}</p></article>
    <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Opportunités</p><p class="mt-2 text-3xl font-bold text-violet-600">{{ $stats['opportunities'] }}</p></article>
    <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Conversion</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['conversion_rate'] }}%</p></article>
    <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">CA potentiel</p><p class="mt-2 text-xl font-bold">{{ number_format($stats['pipeline_value'], 0, ',', ' ') }}</p></article>
    <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Pondéré</p><p class="mt-2 text-xl font-bold text-teal-600">{{ number_format($stats['weighted_value'], 0, ',', ' ') }}</p></article>
    <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Objectif mois</p><p class="mt-2 text-xl font-bold">{{ $stats['goal']->progressPercent() }}%</p><p class="mt-1 text-[11px] text-gp-muted">{{ number_format($stats['goal']->achieved_amount, 0, ',', ' ') }} / {{ number_format($stats['goal']->target_amount, 0, ',', ' ') }}</p></article>
</section>

<section class="mb-6 grid gap-4 xl:grid-cols-2">
    <article class="gp-card"><h2 class="mb-4 text-sm font-bold">Pipeline par étape</h2><canvas id="crm-stage-chart" height="140"></canvas></article>
    <article class="gp-card"><h2 class="mb-4 text-sm font-bold">Leads & CA gagné (6 mois)</h2><canvas id="crm-trend-chart" height="140"></canvas></article>
</section>

<section class="grid gap-4 xl:grid-cols-3">
    <article class="gp-card overflow-hidden p-0">
        <div class="border-b border-gp-border px-5 py-4"><h2 class="text-sm font-bold">Activités du jour</h2></div>
        <ul class="divide-y divide-gp-border">
            @forelse($stats['today_activities'] as $a)
                <li class="px-5 py-3">
                    <a href="{{ route('crm.activities.show', $a) }}" class="font-semibold text-gp-primary hover:underline">{{ $a->subject }}</a>
                    <p class="text-xs text-gp-muted">{{ $a->typeLabel() }} · {{ optional($a->starts_at ?: $a->due_at)->format('H:i') }} · {{ $a->lead?->displayName() }}</p>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-gp-muted">Rien de planifié aujourd’hui</li>
            @endforelse
        </ul>
    </article>
    <article class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4"><h2 class="text-sm font-bold">Leads récents</h2><a href="{{ route('crm.leads.index') }}" class="text-xs text-gp-primary">Liste</a></div>
        <ul class="divide-y divide-gp-border">
            @foreach($stats['recent_leads'] as $l)
                <li class="px-5 py-3 flex items-center justify-between gap-2">
                    <div>
                        <a href="{{ route('crm.leads.show', $l) }}" class="font-semibold hover:text-gp-primary">{{ $l->displayName() }}</a>
                        <p class="text-xs text-gp-muted">{{ $l->typeLabel() }} · {{ $l->sourceLabel() }}</p>
                    </div>
                    <span class="gp-badge {{ $l->statusColor() }}">{{ $l->statusLabel() }}</span>
                </li>
            @endforeach
        </ul>
    </article>
    <article class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4"><h2 class="text-sm font-bold">Opportunités</h2><a href="{{ route('crm.pipeline') }}" class="text-xs text-gp-primary">Pipeline</a></div>
        <ul class="divide-y divide-gp-border">
            @foreach($stats['recent_opps'] as $o)
                <li class="px-5 py-3">
                    <a href="{{ route('crm.opportunities.show', $o) }}" class="font-semibold hover:text-gp-primary">{{ $o->name }}</a>
                    <p class="text-xs text-gp-muted">{{ $o->stageLabel() }} · {{ number_format($o->amount, 0, ',', ' ') }} MAD</p>
                </li>
            @endforeach
        </ul>
    </article>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const stages = @json($stats['by_stage']);
    const months = @json($stats['by_month']);
    const tick = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
    new Chart(document.getElementById('crm-stage-chart'), {
        type: 'doughnut',
        data: { labels: stages.map(s => s.label), datasets: [{ data: stages.map(s => s.count), backgroundColor: ['#0ea5e9','#6366f1','#8b5cf6','#f59e0b','#f97316','#10b981','#f43f5e'] }] },
        options: { plugins: { legend: { position: 'bottom', labels: { color: tick, boxWidth: 10 } } } }
    });
    new Chart(document.getElementById('crm-trend-chart'), {
        type: 'bar',
        data: {
            labels: months.map(m => m.label),
            datasets: [
                { label: 'Leads', data: months.map(m => m.leads), backgroundColor: 'rgba(14,165,233,.55)', borderRadius: 6 },
                { label: 'CA gagné', data: months.map(m => m.won), type: 'line', borderColor: '#10b981', tension: .35, yAxisID: 'y1' },
            ]
        },
        options: {
            plugins: { legend: { labels: { color: tick, boxWidth: 10 } } },
            scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, beginAtZero: true }, y1: { position: 'right', ticks: { color: tick }, grid: { drawOnChartArea: false } } }
        }
    });
</script>
@endsection
