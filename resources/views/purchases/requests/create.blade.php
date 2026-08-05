@extends('layouts.app')

@section('title', 'Nouvelle demande d’achat')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Nouvelle demande d’achat')
@section('subtitle', 'Décrivez le besoin avant création du bon de commande.')

@section('actions')
    <a href="{{ route('purchases.requests.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('purchases._nav')

    <form method="post" action="{{ route('purchases.requests.store') }}" class="mx-auto max-w-3xl space-y-4">
        @csrf
        <section class="gp-card grid gap-4 sm:grid-cols-2">
            <label class="block text-sm sm:col-span-2">
                <span class="mb-1.5 block font-semibold">Titre</span>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Boutique</span>
                <select name="store_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) old('store_id', $workspaceStore?->id) === (string) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm sm:col-span-2">
                <span class="mb-1.5 block font-semibold">Notes</span>
                <textarea name="notes" rows="2" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes') }}</textarea>
            </label>
        </section>

        <section class="gp-card space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold">Articles demandés</h2>
                <button type="button" id="add-req-line" class="gp-btn-secondary text-xs">Ajouter</button>
            </div>
            <div id="req-lines" class="space-y-2">
                <div class="req-line grid gap-2 sm:grid-cols-6">
                    <select name="lines[0][product_id]" required class="rounded-xl border border-gp-border px-3 py-2 text-sm sm:col-span-3 dark:border-white/10 dark:bg-[#0f1614]">
                        <option value="">Produit…</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.001" name="lines[0][quantity]" value="1" required class="rounded-xl border border-gp-border px-3 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]" placeholder="Qté">
                    <input type="text" name="lines[0][notes]" class="rounded-xl border border-gp-border px-3 py-2 text-sm sm:col-span-2 dark:border-white/10 dark:bg-[#0f1614]" placeholder="Note">
                </div>
            </div>
        </section>

        <button class="gp-btn-primary">Créer la demande</button>
    </form>

    <script>
        document.getElementById('add-req-line')?.addEventListener('click', () => {
            const list = document.getElementById('req-lines');
            const i = list.children.length;
            const row = list.firstElementChild.cloneNode(true);
            row.querySelectorAll('select,input').forEach(el => {
                el.name = el.name.replace(/lines\[\d+]/, `lines[${i}]`);
                if (el.tagName === 'SELECT') el.selectedIndex = 0; else if (el.name.includes('quantity')) el.value = 1; else el.value = '';
            });
            list.appendChild(row);
        });
    </script>
@endsection
