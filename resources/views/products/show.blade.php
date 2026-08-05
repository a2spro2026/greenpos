@extends('layouts.app')

@section('title', $product->name)
@section('breadcrumb', 'Catalogue / Produits')
@section('heading', $product->name)
@section('subtitle', $product->sku.' · '.$product->typeLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('update', $product)
            <a href="{{ route('products.edit', $product) }}" class="gp-btn-secondary">Modifier</a>
        @endcan
        @can('duplicate', $product)
            <form method="post" action="{{ route('products.duplicate', $product) }}">@csrf<button class="gp-btn-secondary">Dupliquer</button></form>
        @endcan
        @can('archive', $product)
            @if($product->status !== 'archived')
                <form method="post" action="{{ route('products.archive', $product) }}" onsubmit="return confirm('Archiver ce produit ?')">@csrf<button class="gp-btn-secondary">Archiver</button></form>
            @else
                <form method="post" action="{{ route('products.activate', $product) }}">@csrf<button class="gp-btn-secondary">Réactiver</button></form>
            @endif
        @endcan
        @can('delete', $product)
            <form method="post" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Suppression logique : confirmer ?')">@csrf @method('DELETE')<button class="gp-btn-secondary text-rose-600">Supprimer</button></form>
        @endcan
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="gp-card">
                <div class="flex flex-wrap items-start gap-4">
                    @if($product->imageUrl())
                        <img src="{{ $product->imageUrl() }}" alt="" class="h-28 w-28 rounded-2xl object-cover">
                    @else
                        <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-gp-primary-soft text-2xl font-bold text-gp-primary">{{ strtoupper(substr($product->name, 0, 1)) }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <span class="gp-badge {{ $product->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $product->statusLabel() }}</span>
                        <p class="mt-3 text-sm text-gp-muted">{{ $product->short_description ?: 'Aucune description courte.' }}</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div><p class="text-[11px] uppercase text-gp-muted">Prix vente</p><p class="text-xl font-bold">{{ number_format($product->sale_price, 2, ',', ' ') }}</p></div>
                            <div><p class="text-[11px] uppercase text-gp-muted">Prix effectif</p><p class="text-xl font-bold text-gp-primary">{{ number_format($product->effectiveSalePrice(), 2, ',', ' ') }}</p></div>
                            <div><p class="text-[11px] uppercase text-gp-muted">Taxe</p><p class="text-xl font-bold">{{ number_format($product->tax_rate, 2, ',', ' ') }} %</p></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Détails</h2>
                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    <div><dt class="text-gp-muted">SKU</dt><dd class="font-mono">{{ $product->sku }}</dd></div>
                    <div><dt class="text-gp-muted">Code-barres</dt><dd class="font-mono">{{ $product->barcode ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Catégorie</dt><dd>{{ $product->category?->name ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Marque</dt><dd>{{ $product->brand?->name ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Fournisseur</dt><dd>{{ $product->supplier?->name ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Unité</dt><dd>{{ $product->unit }}</dd></div>
                    @if($canViewPurchase)
                        <div><dt class="text-gp-muted">Prix d’achat</dt><dd>{{ number_format($product->purchase_price, 2, ',', ' ') }}</dd></div>
                    @endif
                    <div><dt class="text-gp-muted">Stock suivi</dt><dd>{{ $product->track_stock ? 'Oui' : 'Non' }}</dd></div>
                </dl>
                @if($product->description)
                    <p class="mt-4 text-sm text-gp-muted whitespace-pre-line">{{ $product->description }}</p>
                @endif
            </section>

            <section class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Variantes</h2>
                @forelse($product->variants as $variant)
                    <div class="flex items-center justify-between border-b border-gp-border py-2 text-sm last:border-0 dark:border-white/10">
                        <div>
                            <p class="font-semibold">{{ $variant->name }}</p>
                            <p class="text-xs text-gp-muted">{{ $variant->sku }} · {{ collect($variant->attributes)->filter()->implode(' / ') }}</p>
                        </div>
                        <p class="font-semibold">{{ $variant->sale_price !== null ? number_format($variant->sale_price, 2, ',', ' ') : '—' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gp-muted">Aucune variante.</p>
                @endforelse
            </section>
        </div>

        <div class="space-y-6">
            <section class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Boutiques</h2>
                @forelse($product->stores as $store)
                    <p class="text-sm py-1">{{ $store->name }} <span class="text-xs text-gp-muted">{{ $store->pivot->is_available ? 'disponible' : 'indisponible' }}</span></p>
                @empty
                    <p class="text-sm text-gp-muted">Aucune boutique associée.</p>
                @endforelse
            </section>

            <section class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Historique</h2>
                <ol class="space-y-3">
                    @forelse($product->changeLogs->take(12) as $log)
                        <li class="text-sm">
                            <p class="font-semibold">{{ $log->action }}</p>
                            <p class="text-xs text-gp-muted">{{ $log->user?->name ?: 'Système' }} · {{ $log->created_at->diffForHumans() }}</p>
                            @if($log->note)<p class="text-xs text-gp-muted">{{ $log->note }}</p>@endif
                        </li>
                    @empty
                        <li class="text-sm text-gp-muted">Aucun historique.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
@endsection
