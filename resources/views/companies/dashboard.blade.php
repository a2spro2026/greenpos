@extends('layouts.app')

@section('title', 'Multi-entreprises')
@section('breadcrumb', 'Administration / Entreprises')
@section('heading', 'Multi-entreprises')
@section('subtitle', 'Pilotez vos organisations indépendantes depuis un seul compte.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('companies.export')
            <a href="{{ route('companies.export') }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('companies.create')
            <a href="{{ route('companies.create') }}" class="gp-btn-primary">Nouvelle entreprise</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('companies._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Entreprises</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Actives</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['active'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Archivées</p><p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['archived'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Boutiques</p><p class="mt-2 text-3xl font-bold">{{ $stats['stores'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Utilisateurs</p><p class="mt-2 text-3xl font-bold">{{ $stats['users'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">CA cumulé</p><p class="mt-2 text-xl font-bold">{{ number_format($stats['revenue_total'], 0, ',', ' ') }} <span class="text-sm text-gp-muted">MAD</span></p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Chiffre d'affaires par entreprise</h2>
            <canvas id="companies-revenue-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Boutiques par entreprise</h2>
            <canvas id="companies-stores-chart" height="140"></canvas>
        </article>
    </section>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($stats['cards'] as $card)
            @php $c = $card['company']; @endphp
            <article class="gp-card group transition hover:-translate-y-0.5 hover:shadow-lg">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        @if($c->logoUrl())
                            <img src="{{ $c->logoUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover ring-1 ring-gp-border dark:ring-white/10">
                        @else
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gp-primary-soft text-sm font-bold text-gp-primary">{{ $c->initials() }}</span>
                        @endif
                        <div class="min-w-0">
                            <a href="{{ route('companies.show', $c) }}" class="block truncate font-bold hover:text-gp-primary">{{ $c->name }}</a>
                            <p class="text-xs text-gp-muted">{{ $c->activity ?: '—' }} · {{ $c->currency }}</p>
                        </div>
                    </div>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ $c->statusColor() }}">{{ $c->statusLabel() }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-gp-bg px-2 py-2 dark:bg-white/5">
                        <p class="text-[10px] uppercase text-gp-muted">CA</p>
                        <p class="text-sm font-bold">{{ number_format($card['revenue'], 0, ',', ' ') }}</p>
                    </div>
                    <div class="rounded-xl bg-gp-bg px-2 py-2 dark:bg-white/5">
                        <p class="text-[10px] uppercase text-gp-muted">Boutiques</p>
                        <p class="text-sm font-bold">{{ $card['stores_count'] }}</p>
                    </div>
                    <div class="rounded-xl bg-gp-bg px-2 py-2 dark:bg-white/5">
                        <p class="text-[10px] uppercase text-gp-muted">Users</p>
                        <p class="text-sm font-bold">{{ $card['users_count'] }}</p>
                    </div>
                </div>
                @if($c->status === 'active')
                    <div class="mt-4 flex justify-end border-t border-gp-border pt-3 dark:border-white/10">
                        <form method="POST" action="{{ route('companies.switch', $c) }}">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-gp-primary hover:underline">Activer</button>
                        </form>
                    </div>
                @endif
            </article>
        @endforeach
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const labels = @json($stats['chart_labels']);
        const revenue = @json($stats['chart_revenue']);
        const stores = @json($stats['chart_stores']);
        new Chart(document.getElementById('companies-revenue-chart'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'CA', data: revenue, backgroundColor: '#0d9488', borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
        new Chart(document.getElementById('companies-stores-chart'), {
            type: 'doughnut',
            data: { labels, datasets: [{ data: stores, backgroundColor: ['#0d9488','#0284c7','#d97706','#7c3aed','#e11d48'] }] },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    </script>
@endsection
