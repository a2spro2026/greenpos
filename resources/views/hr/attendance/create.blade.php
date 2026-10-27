@extends('layouts.app')

@section('title', 'Nouvelle présence')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Nouvelle présence')
@section('subtitle', 'Pointage journalier d’un collaborateur.')

@section('actions')
    <a href="{{ route('hr.attendance.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('hr._nav')

    <form method="post" action="{{ route('hr.attendance.store') }}" class="space-y-4">
        @csrf
        <section class="gp-card">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Employé *</span>
                    <select name="employee_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        <option value="">—</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(old('employee_id') == $emp->id)>{{ $emp->full_name }} ({{ $emp->code }})</option>
                        @endforeach
                    </select>
                    @error('employee_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </label>
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Date *</span>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Boutique</span>
                    <select name="store_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        <option value="">—</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Entrée</span>
                    <input type="time" name="check_in" value="{{ old('check_in') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Sortie</span>
                    <input type="time" name="check_out" value="{{ old('check_out') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Pause (min)</span>
                    <input type="number" name="break_minutes" value="{{ old('break_minutes', 0) }}" min="0" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Heures travaillées</span>
                    <input type="number" step="0.01" name="worked_hours" value="{{ old('worked_hours') }}" placeholder="Auto si entrée/sortie" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Statut *</span>
                    <select name="status" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', 'present') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm sm:col-span-2 lg:col-span-3"><span class="mb-1.5 block font-semibold">Notes</span>
                    <textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes') }}</textarea>
                </label>
            </div>
        </section>
        <button class="gp-btn-primary">Enregistrer</button>
    </form>
@endsection
