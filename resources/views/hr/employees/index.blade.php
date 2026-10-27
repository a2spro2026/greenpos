@extends('layouts.app')

@section('title', 'Employés')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Employés')
@section('subtitle', 'Fiches collaborateurs, rôles et conditions de rémunération.')

@section('actions')
    @can('hr.create')
        <a href="{{ route('hr.employees.create') }}" class="gp-btn-primary">Nouvel employé</a>
    @endcan
@endsection

@section('content')
    @include('hr._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-5">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, code, email…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="status" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous statuts</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="job_role" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous rôles</option>
                @foreach($jobRoles as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['job_role'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($employees->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucun employé</p>
                <p class="mt-2 text-sm text-gp-muted">Créez un collaborateur pour suivre présence et paie.</p>
                @can('hr.create')
                    <a href="{{ route('hr.employees.create') }}" class="gp-btn-primary mt-5">Ajouter le premier</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Rôle</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Rémunération</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($employees as $employee)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-mono text-xs">{{ $employee->code }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $employee->initials() }}</span>
                                        <div>
                                            <a href="{{ route('hr.employees.show', $employee) }}" class="font-semibold hover:text-gp-primary">{{ $employee->full_name }}</a>
                                            <p class="text-xs text-gp-muted">{{ $employee->email ?: $employee->phone ?: '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ $employee->jobRoleLabel() }}</td>
                                <td class="px-4 py-3">{{ $employee->store?->name ?: '—' }}</td>
                                <td class="px-4 py-3">{{ number_format($employee->base_rate, 2, ',', ' ') }} <span class="text-xs text-gp-muted">{{ $employee->payTypeLabel() }}</span></td>
                                <td class="px-4 py-3"><span class="gp-badge {{ $employee->statusColor() }}">{{ $employee->statusLabel() }}</span></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                        <a href="{{ route('hr.employees.show', $employee) }}" class="text-gp-primary hover:underline">Voir</a>
                                        @can('hr.update')
                                            <a href="{{ route('hr.employees.edit', $employee) }}" class="text-gp-muted hover:underline">Modifier</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $employees->links() }}</div>
        @endif
    </section>
@endsection
