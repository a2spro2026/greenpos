@extends('layouts.app')

@section('title', 'Présence en masse')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Saisie en masse')
@section('subtitle', 'Enregistrez la présence de tous les collaborateurs pour une date.')

@section('actions')
    <a href="{{ route('hr.attendance.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('hr._nav')

    <form method="post" action="{{ route('hr.attendance.bulk.store') }}" class="space-y-4">
        @csrf
        <section class="gp-card">
            <div class="mb-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Date *</span>
                    <input type="date" name="date" value="{{ old('date', $date) }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm"><span class="mb-1.5 block font-semibold">Boutique</span>
                    <select name="store_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        <option value="">—</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border text-xs uppercase text-gp-muted dark:border-white/10">
                        <tr>
                            <th class="px-2 py-3">Employé</th>
                            <th class="px-2 py-3">Statut</th>
                            <th class="px-2 py-3">Entrée</th>
                            <th class="px-2 py-3">Sortie</th>
                            <th class="px-2 py-3">Pause</th>
                            <th class="px-2 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($employees as $i => $emp)
                            <tr>
                                <td class="px-2 py-3 font-semibold">
                                    {{ $emp->full_name }}
                                    <input type="hidden" name="rows[{{ $i }}][employee_id]" value="{{ $emp->id }}">
                                </td>
                                <td class="px-2 py-3">
                                    <select name="rows[{{ $i }}][status]" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                                        @foreach($statuses as $key => $label)
                                            <option value="{{ $key }}" @selected($key === 'present')>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-3"><input type="time" name="rows[{{ $i }}][check_in]" value="09:00" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]"></td>
                                <td class="px-2 py-3"><input type="time" name="rows[{{ $i }}][check_out]" value="18:00" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]"></td>
                                <td class="px-2 py-3"><input type="number" name="rows[{{ $i }}][break_minutes]" value="60" min="0" class="w-20 rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]"></td>
                                <td class="px-2 py-3"><input type="text" name="rows[{{ $i }}][notes]" class="w-full rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <button class="gp-btn-primary">Enregistrer toutes les présences</button>
    </form>
@endsection
