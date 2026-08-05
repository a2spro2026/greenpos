@extends('layouts.superadmin')
@section('title', 'Billing SaaS')
@section('breadcrumb', 'Billing / Overview')
@section('heading', 'Facturation & Abonnements')
@section('actions')
    <form method="POST" action="{{ route('superadmin.billing.run-job') }}">@csrf<button class="sa-btn sa-btn-ghost">Exécuter job billing</button></form>
    <a href="{{ route('superadmin.billing.gateways') }}" class="sa-btn sa-btn-ghost">Passerelles</a>
    <a href="{{ route('superadmin.invoices.create') }}" class="sa-btn sa-btn-primary">Nouvelle facture</a>
@endsection
@section('content')
<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8">
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">MRR</p><p class="mt-2 text-2xl font-bold text-sky-400">{{ number_format($stats['mrr'], 0, ',', ' ') }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ARR</p><p class="mt-2 text-2xl font-bold text-violet-300">{{ number_format($stats['arr'], 0, ',', ' ') }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">CA mois</p><p class="mt-2 text-2xl font-bold text-emerald-300">{{ number_format($stats['revenue_month'], 0, ',', ' ') }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Renouvellements</p><p class="mt-2 text-3xl font-bold text-white">{{ $stats['renewals'] }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Actifs</p><p class="mt-2 text-3xl font-bold text-emerald-400">{{ $stats['active'] }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Expirés</p><p class="mt-2 text-3xl font-bold text-rose-300">{{ $stats['expired'] }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Essais</p><p class="mt-2 text-3xl font-bold text-sky-300">{{ $stats['trials'] }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Conversion</p><p class="mt-2 text-3xl font-bold text-amber-300">{{ $stats['conversion_rate'] }}%</p></article>
</section>

<section class="mb-6 grid gap-4 xl:grid-cols-3">
    <article class="sa-card xl:col-span-2">
        <h2 class="mb-4 text-sm font-bold text-white">Revenus & conversions (6 mois)</h2>
        <canvas id="billing-revenue-chart" height="140"></canvas>
    </article>
    <article class="sa-card">
        <h2 class="mb-4 text-sm font-bold text-white">Passerelles</h2>
        <ul class="space-y-3">
            @foreach($stats['gateways'] as $gw)
                <li class="flex items-center justify-between rounded-xl border border-white/5 px-3 py-2 text-sm">
                    <div>
                        <p class="font-semibold text-white">{{ $gw['label'] }}</p>
                        <p class="text-[11px] text-slate-500">{{ $gw['mode'] }} · {{ $gw['status'] }}</p>
                    </div>
                    <span class="sa-badge {{ $gw['enabled'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">
                        {{ $gw['enabled'] ? 'ON' : 'OFF' }}
                    </span>
                </li>
            @endforeach
        </ul>
        <a href="{{ route('superadmin.billing.gateways') }}" class="mt-4 inline-block text-xs font-semibold text-sky-400">Configurer →</a>
    </article>
</section>

<section class="grid gap-4 xl:grid-cols-2">
    <article class="sa-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-white/5 px-5 py-4">
            <h2 class="text-sm font-bold text-white">Factures récentes</h2>
            <a href="{{ route('superadmin.invoices.index') }}" class="text-xs text-sky-400">Historique</a>
        </div>
        <table class="sa-table">
            <thead><tr><th>N°</th><th>Client</th><th>Total</th><th>Statut</th></tr></thead>
            <tbody>
            @forelse($stats['recent_invoices'] as $inv)
                <tr>
                    <td><a href="{{ route('superadmin.invoices.show', $inv) }}" class="sa-mono text-xs text-sky-300 hover:underline">{{ $inv->number }}</a></td>
                    <td>{{ $inv->tenant?->name }}</td>
                    <td>{{ number_format($inv->total, 2, ',', ' ') }}</td>
                    <td><span class="sa-badge {{ $inv->statusColor() }}">{{ $inv->statusLabel() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-10 text-center text-slate-500">Aucune facture</td></tr>
            @endforelse
            </tbody>
        </table>
    </article>
    <article class="sa-card overflow-hidden p-0">
        <div class="border-b border-white/5 px-5 py-4"><h2 class="text-sm font-bold text-white">Échéances proches</h2></div>
        <ul class="divide-y divide-white/5">
            @forelse($stats['expiring'] as $s)
                <li class="flex items-center justify-between px-5 py-3">
                    <div>
                        <a href="{{ route('superadmin.subscriptions.show', $s) }}" class="font-semibold text-sky-300 hover:underline">{{ $s->tenant?->name }}</a>
                        <p class="text-xs text-slate-500">{{ $s->plan?->name }} · {{ $s->ends_at->format('d/m/Y') }}</p>
                    </div>
                    <span class="text-xs text-amber-300">{{ $s->daysRemaining() }} j</span>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">Aucune échéance</li>
            @endforelse
        </ul>
    </article>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const months = @json($stats['by_month']);
    const tick = '#64748b';
    new Chart(document.getElementById('billing-revenue-chart'), {
        type: 'bar',
        data: {
            labels: months.map(m => m.label),
            datasets: [
                { label: 'Revenus', data: months.map(m => m.revenue), backgroundColor: 'rgba(56,189,248,.55)', borderRadius: 6, yAxisID: 'y' },
                { label: 'Conversions', data: months.map(m => m.conversions), type: 'line', borderColor: '#34d399', tension: .35, yAxisID: 'y1' },
            ]
        },
        options: {
            plugins: { legend: { labels: { color: tick, boxWidth: 12 } } },
            scales: {
                x: { ticks: { color: tick } },
                y: { ticks: { color: tick }, beginAtZero: true },
                y1: { position: 'right', ticks: { color: tick }, grid: { drawOnChartArea: false }, beginAtZero: true }
            }
        }
    });
</script>
@endsection
