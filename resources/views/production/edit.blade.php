@extends('layouts.app')

@section('title', 'Modifier production')
@section('breadcrumb', 'Opérations / Production')
@section('heading', 'Modifier '.$production->number)
@section('subtitle', 'Brouillon modifiable.')

@section('actions')
    <a href="{{ route('ops.production.show', $production) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('production._nav')

    <form method="post" action="{{ route('ops.production.update', $production) }}" class="mx-auto max-w-2xl space-y-4">
        @csrf @method('PUT')
        <section class="gp-card space-y-4">
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Boutique *</span>
                <select name="store_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) old('store_id', $production->store_id) === (string) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Produit fini *</span>
                <select name="product_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id', $production->product_id) === (string) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Quantité *</span>
                <input type="number" step="0.001" min="0.001" name="quantity" value="{{ old('quantity', $production->quantity) }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Date</span>
                <input type="date" name="produced_at" value="{{ old('produced_at', $production->produced_at?->format('Y-m-d')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Notes</span>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $production->notes) }}</textarea>
            </label>
        </section>
        <button class="gp-btn-primary">Enregistrer</button>
    </form>
@endsection
