@extends('layouts.app')

@section('title', 'RH')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Tableau de bord RH')
@section('subtitle', 'Collaborateurs, présence et préparation de la paie.')

@section('actions')
    @can('hr.create')
        <a href="{{ route('hr.employees.create') }}" class="gp-btn-primary">Nouvel employé</a>
    @endcan
@endsection

@section('content')
    @include('hr._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Total</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Actifs</p><p class="mt-2 text-3xl font-bold text-gp-primary">{{ $stats['active'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Inactifs</p><p class="mt-2 text-3xl font-bold text-gp-muted">{{ $stats['inactive'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Suspendus</p><p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['suspended'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Nouveaux (30j)</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['new'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Comptes liés</p><p class="mt-2 text-3xl font-bold">{{ $stats['linked'] }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Répartition par rôle</h2>
            <canvas id="hr-roles-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold">Accès rapides</h2>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('hr.employees.index') }}" class="rounded-2xl border border-gp-border px-4 py-4 text-sm font-semibold hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">Liste des employés</a>
                <a href="{{ route('hr.attendance.create') }}" class="rounded-2xl border border-gp-border px-4 py-4 text-sm font-semibold hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">Saisir une présence</a>
                <a href="{{ route('hr.attendance.bulk') }}" class="rounded-2xl border border-gp-border px-4 py-4 text-sm font-semibold hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">Présence en masse</a>
                <a href="{{ route('hr.payroll.create') }}" class="rounded-2xl border border-gp-border px-4 py-4 text-sm font-semibold hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">Calculer une paie</a>
            </div>
        </article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Employés récents</h2>
            <a href="{{ route('hr.employees.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Voir la liste</a>
        </div>
        @if($recent->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-lg font-bold">Aucun employé</p>
                <p class="mt-2 text-sm text-gp-muted">Ajoutez votre premier collaborateur pour démarrer le module RH.</p>
                @can('hr.create')
                    <a href="{{ route('hr.employees.create') }}" class="gp-btn-primary mt-5">Créer un employé</a>
                @endcan
            </div>
        @else
            <div class="divide-y divide-gp-border dark:divide-white/10">
                @foreach($recent as $employee)
                    <a href="{{ route('hr.employees.show', $employee) }}" class="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50/80 dark:hover:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gp-primary-soft text-sm font-bold text-gp-primary">{{ $employee->initials() }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-gp-text dark:text-white">{{ $employee->full_name }}</span>
                            <span class="block truncate text-xs text-gp-muted">{{ $employee->code }} · {{ $employee->jobRoleLabel() }}</span>
                        </span>
                        <span class="gp-badge {{ $employee->statusColor() }}">{{ $employee->statusLabel() }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const byRole = @json($byRole);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#0ea5e9','#f59e0b','#8b5cf6','#ef4444'];
        new Chart(document.getElementById('hr-roles-chart'), {
            type: 'doughnut',
            data: {
                labels: byRole.map(i => i.label),
                datasets: [{ data: byRole.map(i => i.count), backgroundColor: colors }]
            },
            options: { plugins: { legend: { labels: { color: tick } } } }
        });
    </script>
@endsection
