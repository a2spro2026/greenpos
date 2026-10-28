@extends('layouts.app')

@section('title', 'Nouvelle perte')
@section('breadcrumb', 'Opérations / Pertes')
@section('heading', 'Déclarer une perte')
@section('subtitle', 'Sortie multi-lignes — stock diminué immédiatement.')

@section('actions')
    <a href="{{ route('ops.losses.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('production._nav')

    <form method="post" action="{{ route('ops.losses.store') }}" class="space-y-4">
        @csrf
        <section class="gp-card">
            <p class="mb-4 text-xs text-gp-muted">N° suggéré : <span class="font-mono font-semibold">{{ $suggestedNumber }}</span></p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Boutique *</span>
                    <select name="store_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Type *</span>
                    <select name="loss_type" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('loss_type', 'loss') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Date *</span>
                    <input type="date" name="loss_date" value="{{ old('loss_date', now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Responsable</span>
                    <input type="text" name="responsible_name" value="{{ old('responsible_name') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1.5 block font-semibold">Notes</span>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
            </div>
        </section>

        <section class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Lignes produits</h2>
            @error('lines')<p class="mb-2 text-xs text-rose-600">{{ $message }}</p>@enderror
            @for($i = 0; $i < 5; $i++)
                <div class="mb-3 grid gap-3 sm:grid-cols-4">
                    <select name="lines[{{ $i }}][product_id]" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm sm:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
                        <option value="">Produit…</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old("lines.$i.product_id") === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.001" min="0" name="lines[{{ $i }}][quantity]" value="{{ old("lines.$i.quantity") }}" placeholder="Qté" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    <input type="number" step="0.01" min="0" name="lines[{{ $i }}][unit_cost]" value="{{ old("lines.$i.unit_cost") }}" placeholder="Coût u." class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                </div>
            @endfor
            <p class="text-xs text-gp-muted">Laissez vides les lignes inutilisées. Au moins une ligne avec produit et quantité est requise.</p>
        </section>

        <button class="gp-btn-primary">Enregistrer la perte</button>
    </form>
@endsection
