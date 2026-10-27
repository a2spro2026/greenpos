@extends('layouts.app')

@section('title', 'Nouvelle écriture')
@section('breadcrumb', 'Comptabilité / Caisse')
@section('heading', 'Nouvelle écriture de caisse')
@section('subtitle', 'Entrée ou sortie manuelle.')

@section('actions')
    <a href="{{ route('accounting.cash.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('accounting._nav')

    <form method="post" action="{{ route('accounting.cash.store') }}" class="mx-auto max-w-2xl space-y-4">
        @csrf
        <section class="gp-card space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Date *</span>
                    <input type="date" name="entry_date" value="{{ old('entry_date', now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Sens *</span>
                    <select name="direction" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        @foreach($directions as $key => $label)
                            <option value="{{ $key }}" @selected(old('direction', 'in') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1.5 block font-semibold">Libellé *</span>
                    <input type="text" name="label" value="{{ old('label') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Montant *</span>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Source *</span>
                    <select name="source_type" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        @foreach($sourceTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('source_type', 'manual') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Boutique</span>
                    <select name="store_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        <option value="">—</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) old('store_id') === (string) $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Paiement</span>
                    <input type="text" name="payment_method" value="{{ old('payment_method') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1.5 block font-semibold">Notes</span>
                    <textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes') }}</textarea>
                </label>
            </div>
        </section>
        <button class="gp-btn-primary">Enregistrer</button>
    </form>
@endsection
