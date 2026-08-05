@extends('layouts.app')

@section('title', 'Journal d\'audit')
@section('breadcrumb', 'Administration / Audit')
@section('heading', 'Journal d\'audit')
@section('subtitle', 'Traçabilité, conformité et transparence des activités GreenPOS.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('audit.index') }}" class="gp-btn-secondary">Voir les événements</a>
        @can('audit.export')
            <a href="{{ route('audit.export') }}" class="gp-btn-primary">Exporter</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('audit._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi animate-[fadeIn_.4s_ease]">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Aujourd'hui</p>
            <p class="mt-2 text-3xl font-bold text-gp-primary">{{ $stats['today'] }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Critiques (7j)</p>
            <p class="mt-2 text-3xl font-bold text-rose-600">{{ $stats['critical'] }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Cette semaine</p>
            <p class="mt-2 text-3xl font-bold">{{ $stats['week'] }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Total</p>
            <p class="mt-2 text-3xl font-bold text-slate-500">{{ $stats['total'] }}</p>
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Activité (7 jours)</h2>
            <canvas id="audit-day-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Événements par module</h2>
            <canvas id="audit-module-chart" height="140"></canvas>
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-3">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Criticité (14j)</h2>
            <canvas id="audit-severity-chart" height="160"></canvas>
        </article>

        <article class="gp-card xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold">Timeline récente</h2>
                <a href="{{ route('audit.index') }}" class="text-xs font-semibold text-gp-primary hover:underline">Tout voir</a>
            </div>
            <div class="relative space-y-0 pl-4 before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-px before:bg-gp-border dark:before:bg-white/10">
                @forelse($stats['recent'] as $e)
                    <a href="{{ route('audit.show', $e) }}" class="relative mb-4 block pl-6 transition hover:translate-x-0.5">
                        <span class="absolute left-0 top-1.5 h-3.5 w-3.5 rounded-full ring-4 ring-white dark:ring-gp-surface {{ $e->severity === 'critical' ? 'bg-rose-500' : ($e->severity === 'important' ? 'bg-violet-500' : ($e->severity === 'warning' ? 'bg-amber-500' : 'bg-sky-500')) }}"></span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-gp-muted">{{ $e->occurred_at->format('d/m H:i') }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $e->severityColor() }}">{{ $e->severityLabel() }}</span>
                            <span class="rounded-full bg-gp-bg px-2 py-0.5 text-[10px] font-semibold dark:bg-white/5">{{ $e->actionLabel() }}</span>
                        </div>
                        <p class="mt-1 text-sm font-semibold">{{ $e->description ?: $e->subject_label ?: $e->module }}</p>
                        <p class="text-xs text-gp-muted">{{ $e->user?->displayName() ?? 'Système' }} · {{ $e->module }}</p>
                    </a>
                @empty
                    <p class="pl-6 text-sm text-gp-muted">Aucun événement.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Dernières connexions</h2></div>
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @forelse($stats['logins'] as $login)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div>
                            <p class="font-semibold">{{ $login->user?->displayName() ?? '—' }}</p>
                            <p class="text-xs text-gp-muted">{{ $login->ip_address }} · {{ $login->browser }} / {{ $login->platform }}</p>
                        </div>
                        <span class="text-xs text-gp-muted">{{ $login->occurred_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-gp-muted">Aucune connexion enregistrée.</li>
                @endforelse
            </ul>
        </article>

        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Utilisateurs les plus actifs (7j)</h2></div>
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @forelse($stats['top_users'] as $row)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <p class="font-semibold">{{ $row->user?->displayName() ?? 'Utilisateur #'.$row->user_id }}</p>
                        <span class="rounded-full bg-gp-primary-soft px-2.5 py-0.5 text-xs font-bold text-gp-primary">{{ $row->cnt }}</span>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-gp-muted">Pas encore d'activité.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#64748b'];

        const byDay = @json($stats['by_day']);
        new Chart(document.getElementById('audit-day-chart'), {
            type: 'line',
            data: { labels: byDay.map(d => d.label), datasets: [{ data: byDay.map(d => d.count), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35, pointRadius: 3 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, beginAtZero: true } } }
        });

        const byModule = @json($stats['by_module']);
        new Chart(document.getElementById('audit-module-chart'), {
            type: 'bar',
            data: { labels: Object.keys(byModule), datasets: [{ data: Object.values(byModule), backgroundColor: colors, borderRadius: 4 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick, maxRotation: 45 } }, y: { ticks: { color: tick }, beginAtZero: true } } }
        });

        const sevLabels = { info: 'Info', warning: 'Avertissement', important: 'Important', critical: 'Critique' };
        const bySev = @json($stats['by_severity']);
        const sevColors = { info: '#0ea5e9', warning: '#f59e0b', important: '#8b5cf6', critical: '#ef4444' };
        new Chart(document.getElementById('audit-severity-chart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(bySev).map(k => sevLabels[k] || k),
                datasets: [{ data: Object.values(bySev), backgroundColor: Object.keys(bySev).map(k => sevColors[k] || '#64748b') }]
            },
            options: { plugins: { legend: { position: 'bottom', labels: { color: tick, boxWidth: 12 } } } }
        });
    </script>
@endsection
