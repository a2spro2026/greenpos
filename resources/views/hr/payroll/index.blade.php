@extends('layouts.app')

@section('title', 'Paie')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Paie')
@section('subtitle', 'Bulletins mensuels, calculs et paiements.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('hr.payroll.stats') }}" class="gp-btn-secondary">Statistiques</a>
        @can('hr.create')
            <a href="{{ route('hr.payroll.create') }}" class="gp-btn-primary">Calculer une paie</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('hr._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Employé…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <select name="status" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous statuts</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="month" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month === $m)>{{ sprintf('%02d', $m) }}</option>
                @endfor
            </select>
            <input type="number" name="year" value="{{ $year }}" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <button class="gp-btn-primary">Filtrer</button>
        </form>

        @can('hr.create')
            <form method="post" action="{{ route('hr.payroll.bulk') }}" class="mt-3 flex flex-wrap items-end gap-3 border-t border-gp-border pt-3 dark:border-white/10">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <p class="text-sm text-gp-muted">Calculer la paie de tous les employés actifs pour {{ sprintf('%02d/%d', $month, $year) }}.</p>
                <button class="gp-btn-secondary" onclick="return confirm('Lancer le calcul en masse ?')">Calcul en masse</button>
            </form>
        @endcan
    </section>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Bulletins</p><p class="mt-2 text-3xl font-bold">{{ $stats['count'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Brut</p><p class="mt-2 text-2xl font-bold">{{ number_format($stats['gross'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Net</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($stats['net'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Payé</p><p class="mt-2 text-2xl font-bold">{{ number_format($stats['paid'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">En attente</p><p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['pending'] }}</p></article>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($payrolls->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucun bulletin</p>
                <p class="mt-2 text-sm text-gp-muted">Calculez la paie à partir des présences du mois.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Employé</th>
                            <th class="px-4 py-3">Période</th>
                            <th class="px-4 py-3">Jours</th>
                            <th class="px-4 py-3">Brut</th>
                            <th class="px-4 py-3">HS</th>
                            <th class="px-4 py-3">Net</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($payrolls as $payroll)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $payroll->employee?->full_name }}</td>
                                <td class="px-4 py-3">{{ $payroll->periodLabel() }}</td>
                                <td class="px-4 py-3">{{ $payroll->worked_days }} / abs {{ $payroll->absent_days }}</td>
                                <td class="px-4 py-3">{{ number_format($payroll->gross, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3">{{ number_format($payroll->overtime_amount, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 font-bold">{{ number_format($payroll->net, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3"><span class="gp-badge {{ $payroll->statusColor() }}">{{ $payroll->statusLabel() }}</span></td>
                                <td class="px-4 py-3"><a href="{{ route('hr.payroll.show', $payroll) }}" class="text-xs font-semibold text-gp-primary hover:underline">Voir</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $payrolls->links() }}</div>
        @endif
    </section>
@endsection
