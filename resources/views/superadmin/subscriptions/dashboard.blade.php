@extends('layouts.superadmin')
@section('title', 'Abonnements')
@section('breadcrumb', 'Billing / Overview')
@section('heading', 'Gestion des abonnements')
@section('actions')
    <a href="{{ route('superadmin.billing.dashboard') }}" class="sa-btn sa-btn-ghost">Billing</a>
    <a href="{{ route('superadmin.subscriptions.alerts') }}" class="sa-btn sa-btn-ghost">Alertes</a>
    <a href="{{ route('superadmin.subscriptions.create') }}" class="sa-btn sa-btn-primary">Nouvel abonnement</a>
@endsection
@section('content')
<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8">
    <article class="sa-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">MRR</p><p class="mt-2 text-2xl font-bold text-sky-400">{{ number_format($stats['billing']['mrr'] ?? $stats['mrr'], 0, ',', ' ') }}</p></article>
    <article class="sa-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">ARR</p><p class="mt-2 text-2xl font-bold text-violet-300">{{ number_format($stats['billing']['arr'] ?? $stats['arr'], 0, ',', ' ') }}</p></article>
    <article class="sa-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">CA mois</p><p class="mt-2 text-2xl font-bold text-emerald-300">{{ number_format($stats['billing']['revenue_month'] ?? 0, 0, ',', ' ') }}</p></article>
    <article class="sa-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Renouvellements</p><p class="mt-2 text-3xl font-bold text-white">{{ $stats['renewals'] }}</p></article>
    <article class="sa-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actifs</p><p class="mt-2 text-3xl font-bold text-emerald-400">{{ $stats['billing']['active'] ?? ($stats['by_status']['active'] ?? 0) }}</p></article>
    <article class="sa-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expirés</p><p class="mt-2 text-3xl font-bold text-rose-300">{{ $stats['billing']['expired'] ?? ($stats['by_status']['expired'] ?? 0) }}</p></article>
    <article class="sa-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Essais</p><p class="mt-2 text-3xl font-bold text-sky-300">{{ $stats['billing']['trials'] ?? ($stats['by_status']['trialing'] ?? 0) }}</p></article>
    <article class="sa-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conversion</p><p class="mt-2 text-3xl font-bold text-amber-300">{{ $stats['billing']['conversion_rate'] ?? 0 }}%</p></article>
</section>

<section class="mb-6 grid gap-4 xl:grid-cols-2">
    <article class="sa-card">
        <h2 class="mb-4 text-sm font-bold text-white">Évolution (6 mois)</h2>
        <canvas id="sub-trend-chart" height="140"></canvas>
    </article>
    <article class="sa-card">
        <h2 class="mb-4 text-sm font-bold text-white">Revenus facturés</h2>
        <canvas id="sub-revenue-chart" height="140"></canvas>
    </article>
</section>

<section class="grid gap-4 xl:grid-cols-3">
    <article class="sa-card overflow-hidden p-0 xl:col-span-1">
        <div class="border-b border-white/5 px-5 py-4 flex items-center justify-between">
            <h2 class="text-sm font-bold">Alertes</h2>
            <a href="{{ route('superadmin.subscriptions.alerts') }}" class="text-xs text-sky-400">Voir tout</a>
        </div>
        <ul class="divide-y divide-white/5">
            @forelse($stats['alerts'] as $a)
                <li class="px-5 py-3">
                    <span class="sa-badge {{ $a->severityColor() }}">{{ $a->typeLabel() }}</span>
                    <p class="mt-1 text-sm font-semibold text-white">{{ $a->title }}</p>
                    <p class="text-xs text-slate-500">{{ $a->tenant?->name }} · {{ $a->created_at->diffForHumans() }}</p>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">Aucune alerte</li>
            @endforelse
        </ul>
    </article>
    <article class="sa-card overflow-hidden p-0 xl:col-span-1">
        <div class="border-b border-white/5 px-5 py-4"><h2 class="text-sm font-bold">Expirations proches</h2></div>
        <ul class="divide-y divide-white/5">
            @forelse($stats['expiring'] as $s)
                <li class="px-5 py-3">
                    <a href="{{ route('superadmin.subscriptions.show', $s) }}" class="font-semibold text-sky-300 hover:underline">{{ $s->tenant?->name }}</a>
                    <p class="text-xs text-slate-500">{{ $s->plan?->name }} · {{ $s->ends_at->format('d/m/Y') }}</p>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">Rien à signaler</li>
            @endforelse
        </ul>
    </article>
    <article class="sa-card overflow-hidden p-0 xl:col-span-1">
        <div class="border-b border-white/5 px-5 py-4 flex justify-between">
            <h2 class="text-sm font-bold">Récents</h2>
            <a href="{{ route('superadmin.subscriptions.index') }}" class="text-xs text-sky-400">Liste</a>
        </div>
        <ul class="divide-y divide-white/5">
            @foreach($stats['recent'] as $s)
                <li class="flex items-center justify-between gap-2 px-5 py-3">
                    <div>
                        <a href="{{ route('superadmin.subscriptions.show', $s) }}" class="text-sm font-semibold text-white hover:text-sky-300">{{ $s->tenant?->name }}</a>
                        <p class="text-xs text-slate-500">{{ $s->plan?->name }}</p>
                    </div>
                    <span class="sa-badge {{ $s->statusColor() }}">{{ $s->statusLabel() }}</span>
                </li>
            @endforeach
        </ul>
    </article>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const tick = '#64748b';
    const byMonth = @json($stats['by_month']);
    new Chart(document.getElementById('sub-trend-chart'), {
        type: 'bar',
        data: {
            labels: byMonth.map(m => m.label),
            datasets: [
                { label: 'Créés', data: byMonth.map(m => m.created), backgroundColor: 'rgba(56,189,248,.7)', borderRadius: 4 },
                { label: 'Résiliés', data: byMonth.map(m => m.cancelled), backgroundColor: 'rgba(244,63,94,.55)', borderRadius: 4 },
            ]
        },
        options: { plugins: { legend: { labels: { color: tick, boxWidth: 12 } } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, beginAtZero: true } } }
    });
    new Chart(document.getElementById('sub-revenue-chart'), {
        type: 'line',
        data: { labels: byMonth.map(m => m.label), datasets: [{ data: byMonth.map(m => m.revenue), borderColor: '#34d399', backgroundColor: 'rgba(52,211,153,.12)', fill: true, tension: .35 }] },
        options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, beginAtZero: true } } }
    });
</script>
@endsection
