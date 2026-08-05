@extends('layouts.superadmin')

@section('title', 'Dashboard Executive')
@section('breadcrumb', 'Platform / Executive')
@section('heading', 'Dashboard Executive')
@section('actions')
    <a href="{{ route('superadmin.tenants.create') }}" class="sa-btn sa-btn-primary">Nouveau client</a>
@endsection

@section('content')
<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8">
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Clients</p><p class="mt-2 text-3xl font-bold text-white">{{ $stats['clients'] }}</p><p class="mt-1 text-[11px] text-slate-500">+{{ $stats['new_clients_month'] }} ce mois</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Abonnements actifs</p><p class="mt-2 text-3xl font-bold text-emerald-400">{{ $stats['active_subscriptions'] }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Essais gratuits</p><p class="mt-2 text-3xl font-bold text-sky-300">{{ $stats['trials'] }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">MRR</p><p class="mt-2 text-2xl font-bold text-sky-400">{{ number_format($stats['mrr'], 0, ',', ' ') }}</p><p class="mt-1 text-[11px] text-slate-500">MAD / mois</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ARR</p><p class="mt-2 text-2xl font-bold text-violet-300">{{ number_format($stats['arr'], 0, ',', ' ') }}</p><p class="mt-1 text-[11px] text-slate-500">MAD / an</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Croissance</p><p class="mt-2 text-3xl font-bold {{ $stats['growth_monthly'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $stats['growth_monthly'] }}%</p><p class="mt-1 text-[11px] text-slate-500">vs mois précédent</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Utilisateurs</p><p class="mt-2 text-3xl font-bold text-white">{{ $stats['active_users'] }}</p></article>
    <article class="sa-kpi"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Boutiques</p><p class="mt-2 text-3xl font-bold text-amber-300">{{ $stats['total_stores'] }}</p></article>
</section>

<section class="mb-6 grid gap-4 xl:grid-cols-3">
    <article class="sa-card xl:col-span-2">
        <h2 class="mb-4 text-sm font-bold text-white">Revenus SaaS (6 mois)</h2>
        <canvas id="sa-revenue-chart" height="130"></canvas>
    </article>
    <article class="sa-card">
        <h2 class="mb-4 text-sm font-bold text-white">Répartition plans</h2>
        <canvas id="sa-plan-chart" height="130"></canvas>
    </article>
</section>

<section class="mb-6 grid gap-4 xl:grid-cols-2">
    <article class="sa-card">
        <h2 class="mb-4 text-sm font-bold text-white">Nouveaux clients</h2>
        <canvas id="sa-clients-chart" height="120"></canvas>
    </article>
    <article class="sa-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-white/5 px-5 py-4">
            <h2 class="text-sm font-bold text-white">Paiements récents</h2>
            <a href="{{ route('superadmin.payments.index') }}" class="text-xs font-semibold text-sky-400">Tout voir</a>
        </div>
        <div class="overflow-x-auto">
            <table class="sa-table">
                <thead><tr><th>Réf.</th><th>Client</th><th>Montant</th></tr></thead>
                <tbody>
                @forelse($stats['recent_payments'] as $p)
                    <tr>
                        <td class="sa-mono text-xs text-slate-400">{{ $p->number }}</td>
                        <td>{{ $p->tenant?->name }}</td>
                        <td class="font-semibold text-emerald-300">{{ number_format($p->amount, 2, ',', ' ') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-10 text-center text-slate-500">Aucun paiement</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="sa-card overflow-hidden p-0">
    <div class="flex items-center justify-between border-b border-white/5 px-5 py-4">
        <h2 class="text-sm font-bold text-white">Entreprises récentes</h2>
        <a href="{{ route('superadmin.tenants.index') }}" class="text-xs font-semibold text-sky-400">Gérer</a>
    </div>
    <div class="overflow-x-auto">
        <table class="sa-table">
            <thead><tr><th>Entreprise</th><th>Plan</th><th>Statut</th><th>Inscription</th></tr></thead>
            <tbody>
            @foreach($stats['recent_tenants'] as $t)
                <tr>
                    <td><a href="{{ route('superadmin.tenants.show', $t) }}" class="font-semibold text-sky-300 hover:underline">{{ $t->name }}</a></td>
                    <td class="text-slate-400">{{ $t->currentSubscription?->plan?->name ?? '—' }}</td>
                    <td><span class="sa-badge {{ $t->statusColor() }}">{{ $t->statusLabel() }}</span></td>
                    <td class="text-slate-500">{{ $t->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const tick = '#64748b';
    const revenue = @json($stats['revenue_by_month']);
    const byPlan = @json($stats['by_plan']);
    const clients = @json($stats['clients_by_month']);
    const colors = ['#38bdf8','#34d399','#a78bfa','#fbbf24','#f472b6'];
    new Chart(document.getElementById('sa-revenue-chart'), {
        type: 'line',
        data: { labels: revenue.map(r => r.label), datasets: [{ data: revenue.map(r => r.total), borderColor: '#38bdf8', backgroundColor: 'rgba(56,189,248,.12)', fill: true, tension: .35, pointRadius: 3 }] },
        options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, beginAtZero: true } } }
    });
    new Chart(document.getElementById('sa-plan-chart'), {
        type: 'doughnut',
        data: { labels: byPlan.map(p => p.name), datasets: [{ data: byPlan.map(p => p.count), backgroundColor: colors }] },
        options: { plugins: { legend: { position: 'bottom', labels: { color: tick, boxWidth: 12 } } } }
    });
    new Chart(document.getElementById('sa-clients-chart'), {
        type: 'bar',
        data: { labels: clients.map(c => c.label), datasets: [{ data: clients.map(c => c.count), backgroundColor: 'rgba(52,211,153,.65)', borderRadius: 6 }] },
        options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, beginAtZero: true, precision: 0 } } }
    });
</script>
@endsection
