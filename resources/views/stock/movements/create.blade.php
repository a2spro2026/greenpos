@extends('layouts.app')

@section('title', 'Nouveau mouvement')
@section('breadcrumb', 'Catalogue / Stock / Mouvements')
@section('heading', 'Nouveau mouvement')
@section('subtitle', 'Entrée, sortie ou ajustement de stock.')

@section('actions')
    <a href="{{ route('stock.movements.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('stock._nav')

    <form method="post" action="{{ route('stock.movements.store') }}" class="mx-auto max-w-3xl space-y-4">
        @csrf
        <section class="gp-card space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Type</span>
                    <select name="type" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]" required>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $prefill['type']) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Boutique</span>
                    <select name="store_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]" required>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) old('store_id', $prefill['store_id']) === (string) $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                    @error('store_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </label>
            </div>

            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Produit</span>
                <select name="product_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]" required>
                    <option value="">Sélectionner…</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id', $prefill['product_id']) === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
                @error('product_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Quantité <span class="font-normal text-gp-muted">(ajustement = quantité cible)</span></span>
                    <input type="number" step="0.001" name="quantity" value="{{ old('quantity') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @error('quantity')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-semibold">Date</span>
                    <input type="datetime-local" name="moved_at" value="{{ old('moved_at', now()->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                </label>
            </div>

            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Référence</span>
                <input type="text" name="reference" value="{{ old('reference') }}" placeholder="BL-001, INV…" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>

            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Commentaire</span>
                <textarea name="comment" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('comment') }}</textarea>
            </label>
        </section>

        <div class="flex flex-wrap gap-2">
            <button class="gp-btn-primary">Enregistrer le mouvement</button>
            <a href="{{ route('stock.movements.index') }}" class="gp-btn-secondary">Annuler</a>
        </div>
    </form>
@endsection
