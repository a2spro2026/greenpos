@extends('layouts.app')

@section('title', 'Produits')
@section('breadcrumb', 'Catalogue')
@section('heading', 'Produits')
@section('subtitle', 'Référentiel catalogue de l’entreprise — recherche, filtres et pilotage.')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        @can('export', App\Models\Product::class)
            <a href="{{ route('products.export', request()->query()) }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('import', App\Models\Product::class)
            <a href="{{ route('products.import.form') }}" class="gp-btn-secondary">Importer</a>
        @endcan
        @can('create', App\Models\Product::class)
            <a href="{{ route('products.create') }}" class="gp-btn-primary">Nouveau produit</a>
        @endcan
    </div>
@endsection

@section('content')
    @if(session('import_errors'))
        <div class="gp-flash gp-flash-warning">
            <div>
                <p class="font-semibold">Erreurs d’import</p>
                <ul class="mt-1 list-disc pl-5 text-xs">
                    @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Total</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Actifs</p><p class="mt-2 text-3xl font-bold text-gp-primary">{{ $stats['active'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Inactifs</p><p class="mt-2 text-3xl font-bold text-gp-warning">{{ $stats['inactive'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Archivés</p><p class="mt-2 text-3xl font-bold text-gp-muted">{{ $stats['archived'] }}</p></article>
    </section>

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher nom, SKU, code-barres…" class="gp-input lg:col-span-2">
            <select name="status" class="gp-select w-full">
                <option value="">Tous statuts</option>
                @foreach(App\Models\Product::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="type" class="gp-select w-full">
                <option value="">Tous types</option>
                @foreach(App\Models\Product::TYPES as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category_id" class="gp-select w-full">
                <option value="">Catégories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
        <form method="get" class="mt-3 flex flex-wrap gap-3 text-xs text-gp-muted">
            @foreach(request()->except('columns') as $key => $value)
                @if(is_array($value))
                    @continue
                @endif
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <span class="font-semibold">Colonnes :</span>
            @foreach(['image' => 'Image', 'name' => 'Nom', 'sku' => 'SKU', 'barcode' => 'Code-barres', 'category' => 'Catégorie', 'brand' => 'Marque', 'type' => 'Type', 'price' => 'Prix', 'purchase_price' => 'Achat', 'status' => 'Statut', 'updated' => 'Maj'] as $key => $label)
                @if($key === 'purchase_price' && ! $canViewPurchase) @continue @endif
                <label class="inline-flex items-center gap-1">
                    <input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, $columns, true)) onchange="this.form.submit()">
                    {{ $label }}
                </label>
            @endforeach
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="border-b border-gp-border px-4 py-3 space-y-2">
            <x-gp-table-toolbar title="Catalogue produits" />
            <div class="flex flex-wrap gap-3" data-gp-table-cols="products-index"></div>
            <p class="text-[11px] text-gp-muted">Astuce : glissez les en-têtes pour réordonner · coches pour masquer · préférences enregistrées localement.</p>
        </div>
        @if($products->isEmpty())
            <div class="gp-empty">
                <div class="gp-empty-icon" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0H4"/></svg>
                </div>
                <p class="gp-empty-title">Aucun produit</p>
                <p class="gp-empty-text">Ajoutez votre premier produit ou importez un catalogue.</p>
                @can('create', App\Models\Product::class)
                    <a href="{{ route('products.create') }}" class="gp-btn-primary mt-5">Ajouter le premier produit</a>
                @endcan
            </div>
        @else
            <div class="gp-table-wrap">
                <table class="gp-table" data-gp-table="products-index">
                    <thead>
                        <tr>
                            @if(in_array('image', $columns, true))<th data-col="image">Image</th>@endif
                            @if(in_array('name', $columns, true))<th data-col="name">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => ($filters['sort'] ?? '') === 'name' && ($filters['dir'] ?? '') === 'asc' ? 'desc' : 'asc']) }}">Nom</a>
                            </th>@endif
                            @if(in_array('sku', $columns, true))<th data-col="sku">SKU</th>@endif
                            @if(in_array('barcode', $columns, true))<th data-col="barcode">Code-barres</th>@endif
                            @if(in_array('category', $columns, true))<th data-col="category">Catégorie</th>@endif
                            @if(in_array('brand', $columns, true))<th data-col="brand">Marque</th>@endif
                            @if(in_array('type', $columns, true))<th data-col="type">Type</th>@endif
                            @if(in_array('price', $columns, true))<th data-col="price">Prix</th>@endif
                            @if(in_array('purchase_price', $columns, true) && $canViewPurchase)<th data-col="purchase_price">Achat</th>@endif
                            @if(in_array('status', $columns, true))<th data-col="status">Statut</th>@endif
                            @if(in_array('updated', $columns, true))<th data-col="updated">MAJ</th>@endif
                            <th data-col="actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                @if(in_array('image', $columns, true))
                                    <td data-col="image">
                                        @if($product->imageUrl())
                                            <img src="{{ $product->imageUrl() }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                                        @else
                                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                        @endif
                                    </td>
                                @endif
                                @if(in_array('name', $columns, true))
                                    <td data-col="name" class="font-semibold">
                                        <a href="{{ route('products.show', $product) }}" class="hover:text-gp-primary">{{ $product->name }}</a>
                                    </td>
                                @endif
                                @if(in_array('sku', $columns, true))<td data-col="sku" class="font-mono text-xs">{{ $product->sku }}</td>@endif
                                @if(in_array('barcode', $columns, true))<td data-col="barcode" class="font-mono text-xs">{{ $product->barcode ?: '—' }}</td>@endif
                                @if(in_array('category', $columns, true))<td data-col="category">{{ $product->category?->name ?: '—' }}</td>@endif
                                @if(in_array('brand', $columns, true))<td data-col="brand">{{ $product->brand?->name ?: '—' }}</td>@endif
                                @if(in_array('type', $columns, true))<td data-col="type">{{ $product->typeLabel() }}</td>@endif
                                @if(in_array('price', $columns, true))<td data-col="price" class="font-semibold">{{ number_format($product->sale_price, 2, ',', ' ') }}</td>@endif
                                @if(in_array('purchase_price', $columns, true) && $canViewPurchase)<td data-col="purchase_price">{{ number_format($product->purchase_price, 2, ',', ' ') }}</td>@endif
                                @if(in_array('status', $columns, true))
                                    <td data-col="status">
                                        <span class="gp-badge {{ $product->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : ($product->status === 'inactive' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300') }}">{{ $product->statusLabel() }}</span>
                                    </td>
                                @endif
                                @if(in_array('updated', $columns, true))<td data-col="updated" class="text-xs text-gp-muted">{{ $product->updated_at?->diffForHumans() }}</td>@endif
                                <td data-col="actions" class="text-right">
                                    <a href="{{ route('products.show', $product) }}" class="text-xs font-semibold text-gp-primary hover:underline">Ouvrir</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="gp-table-pagination px-4">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
