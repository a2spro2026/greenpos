@extends('layouts.app')

@section('title', $employee->full_name)
@section('breadcrumb', 'Ressources Humaines')
@section('heading', $employee->full_name)
@section('subtitle', ($employee->code ?: 'Sans code').' · '.$employee->jobRoleLabel().' · '.$employee->statusLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('hr.update')
            <a href="{{ route('hr.employees.edit', $employee) }}" class="gp-btn-primary">Modifier</a>
        @endcan
        @can('hr.delete')
            <form method="post" action="{{ route('hr.employees.destroy', $employee) }}" onsubmit="return confirm('Désactiver cet employé ?')">
                @csrf @method('DELETE')
                <button class="gp-btn-secondary text-rose-600">Désactiver</button>
            </form>
        @endcan
    </div>
@endsection

@section('content')
    @include('hr._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-4">
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gp-primary-soft text-lg font-bold text-gp-primary">{{ $employee->initials() }}</span>
        <div>
            <p class="font-bold text-gp-text dark:text-white">{{ $employee->full_name }}</p>
            <p class="text-sm text-gp-muted">{{ $employee->email ?: '—' }} · {{ $employee->phone ?: '—' }}</p>
        </div>
        <span class="gp-badge {{ $employee->statusColor() }}">{{ $employee->statusLabel() }}</span>
        <span class="gp-badge">{{ $employee->jobRoleLabel() }}</span>
    </div>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Ventes liées</p><p class="mt-2 text-2xl font-bold">{{ $performance['sales_count'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">CA vendeur</p><p class="mt-2 text-2xl font-bold">{{ number_format($performance['sales_total'], 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Présences (mois)</p><p class="mt-2 text-2xl font-bold">{{ $performance['attendance_month'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Taux de base</p><p class="mt-2 text-2xl font-bold">{{ number_format($employee->base_rate, 2, ',', ' ') }}</p></article>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="gp-card lg:col-span-2 grid gap-4 sm:grid-cols-2">
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Boutique</p><p class="mt-1 font-semibold">{{ $employee->store?->name ?: '—' }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Compte lié</p><p class="mt-1 font-semibold">{{ $employee->user?->name ?: '—' }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Embauche</p><p class="mt-1">{{ $employee->hire_date?->format('d/m/Y') ?: '—' }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Type de paie</p><p class="mt-1">{{ $employee->payTypeLabel() }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Heures / jour</p><p class="mt-1">{{ $employee->normal_hours_per_day }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">HS ×</p><p class="mt-1">{{ $employee->overtime_multiplier }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Banque</p><p class="mt-1">{{ $employee->bank_name ?: '—' }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">IBAN</p><p class="mt-1 font-mono text-xs">{{ $employee->bank_iban ?: '—' }}</p></div>
            @if($employee->notes)
                <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-gp-muted">Notes</p><p class="mt-1 text-sm">{{ $employee->notes }}</p></div>
            @endif
        </article>

        <aside class="space-y-4">
            <article class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Dernières paies</h2>
                @forelse($performance['payrolls'] as $payroll)
                    <a href="{{ route('hr.payroll.show', $payroll) }}" class="mb-2 flex items-center justify-between rounded-xl px-2 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/5">
                        <span>{{ $payroll->periodLabel() }}</span>
                        <span class="font-semibold">{{ number_format($payroll->net, 2, ',', ' ') }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gp-muted">Aucune paie calculée.</p>
                @endforelse
            </article>

            <article class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Documents</h2>
                @can('hr.update')
                    <form method="post" action="{{ route('hr.employees.documents.store', $employee) }}" enctype="multipart/form-data" class="mb-3 space-y-2">
                        @csrf
                        <input type="text" name="title" placeholder="Titre" class="w-full rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        <input type="file" name="document" required class="w-full text-sm">
                        <button class="gp-btn-secondary text-xs">Ajouter</button>
                    </form>
                @endcan
                <ul class="space-y-2 text-sm">
                    @forelse($employee->documents as $doc)
                        <li class="flex items-center justify-between gap-2">
                            <a href="{{ $doc->url() }}" target="_blank" class="truncate font-semibold text-gp-primary hover:underline">{{ $doc->title }}</a>
                            @can('hr.update')
                                <form method="post" action="{{ route('hr.employees.documents.destroy', [$employee, $doc]) }}" onsubmit="return confirm('Supprimer ?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-rose-600">×</button>
                                </form>
                            @endcan
                        </li>
                    @empty
                        <li class="text-gp-muted">Aucun document.</li>
                    @endforelse
                </ul>
            </article>
        </aside>
    </section>
@endsection
