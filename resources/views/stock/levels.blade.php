@extends('layouts.app')

@section('title', 'Niveaux de stock')
@section('breadcrumb', 'Catalogue / Stock')
@section('heading', 'Liste du stock')
@section('subtitle', 'Quantités disponibles par produit et boutique.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('stock.export')
            <a href="{{ route('stock.levels.export', request()->query()) }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('stock.move')
            <a href="{{ route('stock.movements.create') }}" class="gp-btn-primary">Mouvement</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('stock._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Produit, SKU, code-barres…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="store_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Toutes boutiques</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="category_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Catégories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous statuts</option>
                @foreach(App\Models\StockLevel::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
        <form method="get" class="mt-3 flex flex-wrap gap-3 text-xs text-gp-muted">
            @foreach(request()->except('columns') as $key => $value)
                @if(is_array($value)) @continue @endif
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <span class="font-semibold">Colonnes :</span>
            @foreach(['product' => 'Produit', 'sku' => 'SKU', 'category' => 'Catégorie', 'store' => 'Boutique', 'quantity' => 'Qté', 'min' => 'Min', 'max' => 'Max', 'value' => 'Valeur', 'status' => 'Statut'] as $key => $label)
                <label class="inline-flex items-center gap-1">
                    <input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, $columns, true)) onchange="this.form.submit()">
                    {{ $label }}
                </label>
            @endforeach
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($levels->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucun niveau de stock</p>
                <p class="mt-2 text-sm text-gp-muted">Créez une entrée pour initialiser les quantités catalogue.</p>
                @can('stock.move')
                    <a href="{{ route('stock.movements.create') }}" class="gp-btn-primary mt-5">Initialiser le stock</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            @if(in_array('product', $columns, true))<th class="px-4 py-3">Produit</th>@endif
                            @if(in_array('sku', $columns, true))<th class="px-4 py-3">SKU</th>@endif
                            @if(in_array('category', $columns, true))<th class="px-4 py-3">Catégorie</th>@endif
                            @if(in_array('store', $columns, true))<th class="px-4 py-3">Boutique</th>@endif
                            @if(in_array('quantity', $columns, true))<th class="px-4 py-3"><a href="{{ request()->fullUrlWithQuery(['sort' => 'quantity', 'dir' => ($filters['sort'] ?? '') === 'quantity' && ($filters['dir'] ?? '') === 'asc' ? 'desc' : 'asc']) }}">Quantité</a></th>@endif
                            @if(in_array('min', $columns, true))<th class="px-4 py-3">Min</th>@endif
                            @if(in_array('max', $columns, true))<th class="px-4 py-3">Max</th>@endif
                            @if(in_array('value', $columns, true) && $canViewPurchase)<th class="px-4 py-3">Valeur</th>@endif
                            @if(in_array('status', $columns, true))<th class="px-4 py-3">Statut</th>@endif
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($levels as $level)
                            @php $status = $level->stockStatus(); @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                @if(in_array('product', $columns, true))
                                    <td class="px-4 py-3 font-semibold">{{ $level->product?->name }}</td>
                                @endif
                                @if(in_array('sku', $columns, true))
                                    <td class="px-4 py-3 font-mono text-xs">{{ $level->product?->sku }}</td>
                                @endif
                                @if(in_array('category', $columns, true))
                                    <td class="px-4 py-3">{{ $level->product?->category?->name ?: '—' }}</td>
                                @endif
                                @if(in_array('store', $columns, true))
                                    <td class="px-4 py-3">{{ $level->store?->name }}</td>
                                @endif
                                @if(in_array('quantity', $columns, true))
                                    <td class="px-4 py-3 font-bold">{{ number_format($level->quantity, 3, ',', ' ') }}</td>
                                @endif
                                @if(in_array('min', $columns, true))
                                    <td class="px-4 py-3">{{ number_format($level->min_quantity, 3, ',', ' ') }}</td>
                                @endif
                                @if(in_array('max', $columns, true))
                                    <td class="px-4 py-3">{{ $level->max_quantity !== null ? number_format($level->max_quantity, 3, ',', ' ') : '—' }}</td>
                                @endif
                                @if(in_array('value', $columns, true) && $canViewPurchase)
                                    <td class="px-4 py-3">{{ number_format($level->valuation(), 2, ',', ' ') }}</td>
                                @endif
                                @if(in_array('status', $columns, true))
                                    <td class="px-4 py-3">
                                        <span class="gp-badge {{ $status === 'out' ? 'bg-rose-100 text-rose-700' : ($status === 'low' ? 'bg-amber-100 text-amber-800' : ($status === 'over' ? 'bg-sky-100 text-sky-800' : '')) }}">{{ $level->statusLabel() }}</span>
                                    </td>
                                @endif
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @can('stock.move')
                                            <a href="{{ route('stock.movements.create', ['product_id' => $level->product_id, 'store_id' => $level->store_id, 'type' => 'in']) }}" class="text-xs font-semibold text-gp-primary hover:underline">Entrée</a>
                                            <a href="{{ route('stock.movements.create', ['product_id' => $level->product_id, 'store_id' => $level->store_id, 'type' => 'out']) }}" class="text-xs font-semibold text-sky-600 hover:underline">Sortie</a>
                                        @endcan
                                        @can('stock.adjust')
                                            <details class="relative">
                                                <summary class="cursor-pointer text-xs font-semibold text-gp-muted hover:text-gp-text">Seuils</summary>
                                                <form method="post" action="{{ route('stock.levels.update', $level) }}" class="absolute right-0 z-10 mt-2 w-56 rounded-xl border border-gp-border bg-white p-3 shadow-lg dark:border-white/10 dark:bg-[#121a17]">
                                                    @csrf @method('PATCH')
                                                    <label class="mb-2 block text-xs">Min<input type="number" step="0.001" name="min_quantity" value="{{ $level->min_quantity }}" class="mt-1 w-full rounded-lg border border-gp-border px-2 py-1.5 text-sm dark:border-white/10 dark:bg-[#0f1614]"></label>
                                                    <label class="mb-2 block text-xs">Max<input type="number" step="0.001" name="max_quantity" value="{{ $level->max_quantity }}" class="mt-1 w-full rounded-lg border border-gp-border px-2 py-1.5 text-sm dark:border-white/10 dark:bg-[#0f1614]"></label>
                                                    <button class="gp-btn-primary w-full text-xs">Enregistrer</button>
                                                </form>
                                            </details>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $levels->links() }}</div>
        @endif
    </section>
@endsection
