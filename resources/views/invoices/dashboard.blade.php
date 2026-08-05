@extends('layouts.app')

@section('title', 'Facturation')
@section('breadcrumb', 'Finance / Facturation')
@section('heading', 'Dashboard Facturation')
@section('subtitle', 'Suivi des factures, encaissements et échéances.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('invoices.export')
            <a href="{{ route('invoices.export') }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('invoices.create')
            <a href="{{ route('invoices.create') }}" class="gp-btn-primary">Nouvelle facture</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('invoices._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Total factures</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Payées</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['paid'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">En attente</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['pending'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">En retard</p><p class="mt-2 text-3xl font-bold text-orange-600">{{ $stats['overdue'] }}</p></article>
        <article class="gp-kpi sm:col-span-2 xl:col-span-1 2xl:col-span-1"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">CA facturé</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($stats['revenue'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi sm:col-span-2 xl:col-span-1 2xl:col-span-1"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Reste à encaisser</p><p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($stats['outstanding'], 2, ',', ' ') }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Évolution du chiffre d'affaires</h2>
            <canvas id="inv-revenue-chart" height="140"></canvas>
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Échéances proches (7 jours)</h2></div>
            @if($dueSoon->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune échéance imminente.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($dueSoon as $inv)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <a href="{{ route('invoices.show', $inv) }}" class="font-semibold text-gp-primary hover:underline">{{ $inv->number }}</a>
                                <p class="text-xs text-gp-muted">{{ $inv->customer?->displayName() }} · {{ optional($inv->due_at)->format('d/m/Y') }}</p>
                            </div>
                            <span class="font-bold">{{ number_format($inv->balance_due, 2, ',', ' ') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Dernières factures</h2>
            <a href="{{ route('invoices.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Tout voir</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3">N°</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($recent as $inv)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('invoices.show', $inv) }}" class="text-gp-primary hover:underline">{{ $inv->number }}</a></td>
                            <td class="px-4 py-3">{{ $inv->customer?->displayName() }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ optional($inv->invoiced_at)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-bold">{{ number_format($inv->total_ttc, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $inv->statusColor() }}">{{ $inv->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gp-muted">Aucune facture.</td></tr>
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
        new Chart(document.getElementById('inv-revenue-chart'), {
            type: 'line',
            data: {
                labels: monthly.map(i => i.label),
                datasets: [{ data: monthly.map(i => i.total), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35, pointRadius: 4 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
    </script>
@endsection
