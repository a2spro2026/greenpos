@extends('layouts.app')

@section('title', 'Fournisseurs')
@section('breadcrumb', 'Approvisionnement / Fournisseurs')
@section('heading', 'Dashboard Fournisseurs')
@section('subtitle', 'Référentiel partenaires et performance d’approvisionnement.')

@section('actions')
    @can('suppliers.create')
        <a href="{{ route('suppliers.create') }}" class="gp-btn-primary">Nouveau fournisseur</a>
    @endcan
@endsection

@section('content')
    @include('suppliers._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Total</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Actifs</p><p class="mt-2 text-3xl font-bold text-gp-primary">{{ $stats['active'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Nouveaux (30j)</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['new'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Achats du mois</p><p class="mt-2 text-2xl font-bold">{{ number_format($stats['month_purchases'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">À risque</p><p class="mt-2 text-3xl font-bold text-gp-warning">{{ $stats['risk'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Inactifs</p><p class="mt-2 text-3xl font-bold text-gp-muted">{{ $stats['inactive'] }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Évolution des créations</h2>
            <canvas id="suppliers-evolution-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Achats par fournisseur</h2>
            <canvas id="suppliers-spend-chart" height="140"></canvas>
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Derniers fournisseurs</h2>
            <a href="{{ route('suppliers.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Voir la liste</a>
        </div>
        @if($recent->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-lg font-bold">Aucun fournisseur</p>
                <p class="mt-2 text-sm text-gp-muted">Ajoutez votre premier partenaire d’approvisionnement.</p>
                @can('suppliers.create')
                    <a href="{{ route('suppliers.create') }}" class="gp-btn-primary mt-5">Créer un fournisseur</a>
                @endcan
            </div>
        @else
            <div class="divide-y divide-gp-border dark:divide-white/10">
                @foreach($recent as $supplier)
                    <a href="{{ route('suppliers.show', $supplier) }}" class="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50/80 dark:hover:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gp-primary-soft text-sm font-bold text-gp-primary">{{ $supplier->initials() }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-gp-text dark:text-white">{{ $supplier->name }}</span>
                            <span class="block truncate text-xs text-gp-muted">{{ $supplier->code }} · {{ $supplier->city ?: '—' }} · {{ $supplier->company_name ?: $supplier->email }}</span>
                        </span>
                        <span class="gp-badge {{ $supplier->statusColor() }}">{{ $supplier->statusLabel() }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const evolution = @json($evolution);
        const bySpend = @json($bySpend);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#0ea5e9','#f59e0b','#8b5cf6','#ef4444','#14b8a6'];

        new Chart(document.getElementById('suppliers-evolution-chart'), {
            type: 'line',
            data: { labels: evolution.map(i => i.label), datasets: [{ data: evolution.map(i => i.count), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, precision: 0 } } }
        });
        new Chart(document.getElementById('suppliers-spend-chart'), {
            type: 'doughnut',
            data: { labels: bySpend.map(i => i.name), datasets: [{ data: bySpend.map(i => i.total), backgroundColor: colors, borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom', labels: { color: tick } } } }
        });
    </script>
@endsection
