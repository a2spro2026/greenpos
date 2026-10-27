@extends('layouts.app')

@section('title', 'Présence')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Présence')
@section('subtitle', 'Pointages journaliers, saisie en masse et résumé mensuel.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('hr.create')
            <a href="{{ route('hr.attendance.bulk') }}" class="gp-btn-secondary">Saisie en masse</a>
            <a href="{{ route('hr.attendance.create') }}" class="gp-btn-primary">Nouvelle présence</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('hr._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-7">
            <input type="hidden" name="view" value="{{ $view }}">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Employé…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <select name="employee_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous employés</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $emp->id)>{{ $emp->full_name }}</option>
                @endforeach
            </select>
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
            <select name="view" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="list" @selected($view === 'list')>Liste</option>
                <option value="calendar" @selected($view === 'calendar')>Calendrier</option>
                <option value="summary" @selected($view === 'summary')>Résumé</option>
            </select>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Enregistrements</p><p class="mt-2 text-3xl font-bold">{{ $summary['totals']['records'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Présents</p><p class="mt-2 text-3xl font-bold text-gp-primary">{{ $summary['totals']['present'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Absents</p><p class="mt-2 text-3xl font-bold text-rose-600">{{ $summary['totals']['absent'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Heures</p><p class="mt-2 text-3xl font-bold">{{ number_format($summary['totals']['worked_hours'], 1, ',', ' ') }}</p></article>
    </section>

    @if($view === 'summary')
        <section class="gp-card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Employé</th>
                            <th class="px-4 py-3">Présents</th>
                            <th class="px-4 py-3">Absents</th>
                            <th class="px-4 py-3">Congés</th>
                            <th class="px-4 py-3">Retards</th>
                            <th class="px-4 py-3">Heures</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @forelse($summary['by_employee'] as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $row['name'] }}</td>
                                <td class="px-4 py-3">{{ $row['present'] }}</td>
                                <td class="px-4 py-3">{{ $row['absent'] }}</td>
                                <td class="px-4 py-3">{{ $row['leave'] }}</td>
                                <td class="px-4 py-3">{{ $row['late'] }}</td>
                                <td class="px-4 py-3">{{ number_format($row['worked_hours'], 1, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-gp-muted">Aucune donnée pour cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @elseif($view === 'calendar')
        <section class="gp-card">
            <h2 class="mb-4 text-sm font-bold">{{ $summary['period'] }}</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($summary['records'] as $record)
                    <div class="rounded-2xl border border-gp-border p-3 text-sm dark:border-white/10">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold">{{ $record->date->format('d/m') }}</span>
                            <span class="gp-badge {{ $record->statusColor() }}">{{ $record->statusLabel() }}</span>
                        </div>
                        <p class="mt-2 text-gp-muted">{{ $record->employee?->full_name }}</p>
                        <p class="mt-1 text-xs">{{ $record->check_in ? substr($record->check_in, 0, 5) : '—' }} → {{ $record->check_out ? substr($record->check_out, 0, 5) : '—' }} · {{ $record->worked_hours !== null ? number_format($record->worked_hours, 1, ',', ' ').' h' : '—' }}</p>
                    </div>
                @empty
                    <p class="text-gp-muted sm:col-span-2">Aucune présence ce mois.</p>
                @endforelse
            </div>
        </section>
    @else
        <section class="gp-card overflow-hidden p-0">
            @if($records->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-lg font-bold">Aucune présence</p>
                    <p class="mt-2 text-sm text-gp-muted">Enregistrez les pointages du mois.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Employé</th>
                                <th class="px-4 py-3">Entrée</th>
                                <th class="px-4 py-3">Sortie</th>
                                <th class="px-4 py-3">Heures</th>
                                <th class="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gp-border dark:divide-white/10">
                            @foreach($records as $record)
                                <tr>
                                    <td class="px-4 py-3">{{ $record->date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $record->employee?->full_name }}</td>
                                    <td class="px-4 py-3">{{ $record->check_in ? substr($record->check_in, 0, 5) : '—' }}</td>
                                    <td class="px-4 py-3">{{ $record->check_out ? substr($record->check_out, 0, 5) : '—' }}</td>
                                    <td class="px-4 py-3">{{ $record->worked_hours !== null ? number_format($record->worked_hours, 2, ',', ' ') : '—' }}</td>
                                    <td class="px-4 py-3"><span class="gp-badge {{ $record->statusColor() }}">{{ $record->statusLabel() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $records->links() }}</div>
            @endif
        </section>
    @endif
@endsection
