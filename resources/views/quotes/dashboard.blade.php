@extends('layouts.app')

@section('title', 'Devis')
@section('breadcrumb', 'Ventes / Devis')
@section('heading', 'Dashboard Devis')
@section('subtitle', 'Suivi commercial des propositions et conversions.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('quotes.export')
            <a href="{{ route('quotes.export') }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('quotes.create')
            <a href="{{ route('quotes.create') }}" class="gp-btn-primary">Nouveau devis</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('quotes._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Total devis</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">En attente</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['pending'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Acceptés</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['accepted'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Refusés</p><p class="mt-2 text-3xl font-bold text-rose-600">{{ $stats['refused'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Montant total</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($stats['amount'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Taux conversion</p><p class="mt-2 text-3xl font-bold">{{ $stats['conversion_rate'] }}%</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Devis mensuels (montant)</h2>
            <canvas id="quotes-monthly-chart" height="140"></canvas>
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Devis à relancer</h2></div>
            @if($followUp->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucun devis à relancer cette semaine.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($followUp as $q)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <a href="{{ route('quotes.show', $q) }}" class="font-semibold text-gp-primary hover:underline">{{ $q->number }}</a>
                                <p class="text-xs text-gp-muted">{{ $q->customer?->displayName() }} · validité {{ optional($q->valid_until)->format('d/m/Y') }}</p>
                            </div>
                            <span class="font-bold">{{ number_format($q->total_ttc, 2, ',', ' ') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Derniers devis</h2>
            <a href="{{ route('quotes.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Tout voir</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr><th class="px-4 py-3">N°</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Statut</th></tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($recent as $q)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('quotes.show', $q) }}" class="text-gp-primary hover:underline">{{ $q->number }}</a></td>
                            <td class="px-4 py-3">{{ $q->customer?->displayName() }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ optional($q->quoted_at)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-bold">{{ number_format($q->total_ttc, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $q->statusColor() }}">{{ $q->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gp-muted">Aucun devis.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const monthly = @json($monthly);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        new Chart(document.getElementById('quotes-monthly-chart'), {
            type: 'bar',
            data: { labels: monthly.map(i => i.label), datasets: [{ data: monthly.map(i => i.total), backgroundColor: '#0ea5e9', borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
    </script>
@endsection
