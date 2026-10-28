@extends('layouts.app')

@section('title', 'Calculer une paie')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Calculer une paie')
@section('subtitle', 'Aperçu basé sur les présences du mois, puis enregistrement.')

@section('actions')
    <a href="{{ route('hr.payroll.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('hr._nav')

    <form method="get" action="{{ route('hr.payroll.create') }}" class="gp-card mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <label class="block text-sm lg:col-span-2"><span class="mb-1.5 block font-semibold">Employé</span>
            <select name="employee_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]" onchange="this.form.submit()">
                <option value="">— Choisir —</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected((string) $selectedEmployeeId === (string) $emp->id)>{{ $emp->full_name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm"><span class="mb-1.5 block font-semibold">Mois</span>
            <select name="month" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month === $m)>{{ sprintf('%02d', $m) }}</option>
                @endfor
            </select>
        </label>
        <label class="block text-sm"><span class="mb-1.5 block font-semibold">Année</span>
            <input type="number" name="year" value="{{ $year }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <div class="flex items-end"><button class="gp-btn-secondary w-full">Prévisualiser</button></div>
    </form>

    @if($preview)
        <form method="post" action="{{ route('hr.payroll.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $preview['employee_id'] }}">
            <input type="hidden" name="year" value="{{ $preview['year'] }}">
            <input type="hidden" name="month" value="{{ $preview['month'] }}">
            <input type="hidden" name="pay_type" value="{{ $preview['pay_type'] }}">
            <input type="hidden" name="base_rate" value="{{ $preview['base_rate'] }}">

            <section class="mb-2 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Jours travaillés</p><p class="mt-2 text-3xl font-bold">{{ $preview['worked_days'] }}</p></article>
                <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Absences</p><p class="mt-2 text-3xl font-bold">{{ $preview['absent_days'] }}</p></article>
                <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Heures HS</p><p class="mt-2 text-3xl font-bold">{{ number_format($preview['overtime_hours'], 1, ',', ' ') }}</p></article>
                <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Net estimé</p><p class="mt-2 text-3xl font-bold text-gp-primary">{{ number_format($preview['net'], 2, ',', ' ') }}</p></article>
            </section>

            <section class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Ajustements</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="block text-sm"><span class="mb-1.5 block font-semibold">Prime</span><input type="number" step="0.01" name="prime" value="{{ old('prime', $overrides['prime'] ?? $preview['prime']) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
                    <label class="block text-sm"><span class="mb-1.5 block font-semibold">Bonus</span><input type="number" step="0.01" name="bonus" value="{{ old('bonus', $overrides['bonus'] ?? $preview['bonus']) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
                    <label class="block text-sm"><span class="mb-1.5 block font-semibold">Avance</span><input type="number" step="0.01" name="advance" value="{{ old('advance', $overrides['advance'] ?? $preview['advance']) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
                    <label class="block text-sm"><span class="mb-1.5 block font-semibold">Retenue</span><input type="number" step="0.01" name="retention" value="{{ old('retention', $overrides['retention'] ?? $preview['retention']) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
                    <label class="block text-sm sm:col-span-2 lg:col-span-4"><span class="mb-1.5 block font-semibold">Notes</span><textarea name="notes" rows="2" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $overrides['notes'] ?? '') }}</textarea></label>
                </div>
                <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-white/5"><span class="text-gp-muted">Brut</span><span class="font-semibold">{{ number_format($preview['gross'], 2, ',', ' ') }}</span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-white/5"><span class="text-gp-muted">Heures supp.</span><span class="font-semibold">{{ number_format($preview['overtime_amount'], 2, ',', ' ') }}</span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-white/5"><span class="text-gp-muted">Absences</span><span class="font-semibold">− {{ number_format($preview['absence_deduction'], 2, ',', ' ') }}</span></div>
                </div>
            </section>

            <button class="gp-btn-primary">Enregistrer le bulletin</button>
        </form>
    @else
        <section class="gp-card px-6 py-12 text-center">
            <p class="text-lg font-bold">Sélectionnez un employé</p>
            <p class="mt-2 text-sm text-gp-muted">La prévisualisation utilise les présences du mois choisi.</p>
        </section>
    @endif
@endsection
