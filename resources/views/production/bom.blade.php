@extends('layouts.app')

@section('title', 'Nomenclatures BOM')
@section('breadcrumb', 'Opérations / Production')
@section('heading', 'Nomenclatures (BOM)')
@section('subtitle', 'Définissez les composants d’un produit fini.')

@section('actions')
    <a href="{{ route('ops.production.index') }}" class="gp-btn-secondary">Productions</a>
@endsection

@section('content')
    @include('production._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <form method="get" class="gp-card mb-4 flex flex-wrap items-end gap-3">
        <label class="block flex-1 text-sm min-w-[200px]">
            <span class="mb-1.5 block font-semibold">Produit fini</span>
            <select name="product_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Choisir…</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected((string) $productId === (string) $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
        </label>
    </form>

    @if($productId)
        <form method="post" action="{{ route('ops.production.bom.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="product_id" value="{{ $productId }}">
            <section class="gp-card overflow-hidden p-0">
                <div class="border-b border-gp-border px-4 py-3 text-sm font-bold dark:border-white/10">Composants</div>
                <div class="overflow-x-auto p-4" id="bom-rows">
                    @php $rows = old('components', $bom->map(fn($b) => ['component_product_id' => $b->component_product_id, 'quantity' => $b->quantity, 'unit_cost' => $b->unit_cost])->all() ?: [['component_product_id' => '', 'quantity' => '', 'unit_cost' => '']]); @endphp
                    @foreach($rows as $i => $row)
                        <div class="mb-3 grid gap-3 sm:grid-cols-3">
                            <select name="components[{{ $i }}][component_product_id]" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                                <option value="">Composant…</option>
                                @foreach($components as $c)
                                    <option value="{{ $c->id }}" @selected((string) ($row['component_product_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.001" min="0.001" name="components[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}" placeholder="Quantité" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                            <input type="number" step="0.01" min="0" name="components[{{ $i }}][unit_cost]" value="{{ $row['unit_cost'] ?? '' }}" placeholder="Coût unitaire" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        </div>
                    @endforeach
                    @for($i = count($rows); $i < count($rows) + 3; $i++)
                        <div class="mb-3 grid gap-3 sm:grid-cols-3">
                            <select name="components[{{ $i }}][component_product_id]" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                                <option value="">Composant…</option>
                                @foreach($components as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.001" min="0.001" name="components[{{ $i }}][quantity]" placeholder="Quantité" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                            <input type="number" step="0.01" min="0" name="components[{{ $i }}][unit_cost]" placeholder="Coût unitaire" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        </div>
                    @endfor
                </div>
            </section>
            <button class="gp-btn-primary">Enregistrer la nomenclature</button>
        </form>
    @endif
@endsection
