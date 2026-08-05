@extends('layouts.app')

@section('title', $supplier->name)
@section('breadcrumb', 'Approvisionnement / Fournisseurs')
@section('heading', $supplier->name)
@section('subtitle', ($supplier->code ?: 'Sans code').' · '.$supplier->statusLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('suppliers.print')
            <a href="{{ route('suppliers.print', $supplier) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('suppliers.update')
            <a href="{{ route('suppliers.edit', $supplier) }}" class="gp-btn-primary">Modifier</a>
        @endcan
        @can('suppliers.delete')
            <form method="post" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Archiver ce fournisseur ?')">
                @csrf @method('DELETE')
                <button class="gp-btn-secondary text-rose-600">Archiver</button>
            </form>
        @endcan
    </div>
@endsection

@section('content')
    @include('suppliers._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-4">
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gp-primary-soft text-lg font-bold text-gp-primary">{{ $supplier->initials() }}</span>
        <div>
            <p class="font-bold text-gp-text dark:text-white">{{ $supplier->company_name ?: $supplier->name }}</p>
            <p class="text-sm text-gp-muted">{{ $supplier->email ?: '—' }} · {{ $supplier->phone ?: '—' }}</p>
        </div>
        <span class="gp-badge {{ $supplier->statusColor() }}">{{ $supplier->statusLabel() }}</span>
    </div>

    @php
        $tabs = [
            'overview' => 'Présentation',
            'orders' => 'Achats',
            'products' => 'Produits',
            'stats' => 'Statistiques',
            'documents' => 'Documents',
            'history' => 'Historique',
        ];
    @endphp
    <nav class="mb-5 flex gap-2 overflow-x-auto pb-1">
        @foreach($tabs as $key => $label)
            <a href="{{ route('suppliers.show', ['supplier' => $supplier, 'tab' => $key]) }}"
               class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'bg-gp-primary text-white' : 'bg-white text-gp-muted ring-1 ring-gp-border dark:bg-white/5 dark:ring-white/10' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if($tab === 'overview')
        <section class="grid gap-4 lg:grid-cols-3">
            <article class="gp-card lg:col-span-2 grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Catégorie</p><p class="mt-1 font-semibold">{{ $supplier->categoryLabel() }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Société</p><p class="mt-1 font-semibold">{{ $supplier->company_name ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Adresse</p><p class="mt-1">{{ $supplier->address ?: '—' }}</p><p class="text-sm text-gp-muted">{{ collect([$supplier->postal_code, $supplier->city, $supplier->region, $supplier->country])->filter()->implode(', ') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Site web</p><p class="mt-1">{{ $supplier->website ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Conditions paiement</p><p class="mt-1">{{ $supplier->payment_terms ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Délai livraison</p><p class="mt-1">{{ $supplier->delivery_delay_days !== null ? $supplier->delivery_delay_days.' jours' : '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">N° fiscal</p><p class="mt-1">{{ $supplier->tax_id ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Devise</p><p class="mt-1">{{ $supplier->currency ?: '—' }}</p></div>
                @if($supplier->notes)
                    <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-gp-muted">Remarques</p><p class="mt-1 text-sm">{{ $supplier->notes }}</p></div>
                @endif
            </article>
            <aside class="gp-card space-y-3 text-sm">
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Mobile</p><p class="mt-1 font-semibold">{{ $supplier->mobile ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Créé par</p><p class="mt-1">{{ $supplier->creator?->name ?: '—' }}</p></div>
                <a href="{{ route('purchases.orders.create') }}" class="gp-btn-primary inline-flex w-full justify-center">Nouveau BC</a>
            </aside>
        </section>
    @endif

    @if($tab === 'orders')
        <section class="gp-card overflow-hidden p-0">
            @if($orders->isEmpty())
                <div class="px-6 py-14 text-center text-sm text-gp-muted">Aucune commande pour ce fournisseur.</div>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-gp-muted dark:bg-white/5"><tr><th class="px-4 py-3 text-left">N°</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Boutique</th><th class="px-4 py-3 text-right">Montant</th><th class="px-4 py-3 text-left">Statut</th></tr></thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($orders as $order)
                            <tr>
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('purchases.orders.show', $order) }}" class="hover:text-gp-primary">{{ $order->number }}</a></td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ optional($order->ordered_at)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ $order->store?->name }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ number_format($order->total_ttc, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3"><span class="gp-badge {{ $order->statusColor() }}">{{ $order->statusLabel() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $orders->links() }}</div>
            @endif
        </section>
    @endif

    @if($tab === 'products')
        <section class="gp-card overflow-hidden p-0">
            @if($products->isEmpty())
                <div class="px-6 py-14 text-center text-sm text-gp-muted">Aucun produit lié.</div>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-gp-muted dark:bg-white/5"><tr><th class="px-4 py-3 text-left">Produit</th><th class="px-4 py-3 text-left">SKU</th><th class="px-4 py-3 text-left">Catégorie</th><th class="px-4 py-3 text-right">Prix achat</th></tr></thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($products as $product)
                            <tr>
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('products.show', $product) }}" class="hover:text-gp-primary">{{ $product->name }}</a></td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                                <td class="px-4 py-3">{{ $product->category?->name ?: '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($product->purchase_price, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $products->links() }}</div>
            @endif
        </section>
    @endif

    @if($tab === 'stats')
        <section class="grid gap-4 sm:grid-cols-3">
            <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Commandes</p><p class="mt-2 text-3xl font-bold">{{ $orderStats['count'] }}</p></article>
            <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Montant acheté</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($orderStats['total'], 2, ',', ' ') }}</p></article>
            <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Dernière commande</p><p class="mt-2 text-xl font-bold">{{ $orderStats['last'] ? \Illuminate\Support\Carbon::parse($orderStats['last'])->format('d/m/Y') : '—' }}</p></article>
        </section>
    @endif

    @if($tab === 'documents')
        <section class="mb-4 grid gap-4 lg:grid-cols-3">
            <article class="gp-card lg:col-span-2 overflow-hidden p-0">
                @if($supplier->documents->isEmpty())
                    <div class="px-6 py-14 text-center text-sm text-gp-muted">Aucun document.</div>
                @else
                    <ul class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($supplier->documents as $document)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                                <div>
                                    <p class="font-semibold">{{ $document->title }}</p>
                                    <p class="text-xs text-gp-muted">{{ $document->category }} · {{ $document->uploader?->name }} · {{ $document->created_at?->format('d/m/Y') }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ $document->url() }}" target="_blank" class="text-xs font-semibold text-gp-primary hover:underline">Ouvrir</a>
                                    @can('suppliers.update')
                                        <form method="post" action="{{ route('suppliers.documents.destroy', [$supplier, $document]) }}" onsubmit="return confirm('Supprimer ce document ?')">@csrf @method('DELETE')<button class="text-xs font-semibold text-rose-600">Supprimer</button></form>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
            @can('suppliers.update')
                <aside class="gp-card">
                    <h2 class="mb-3 text-sm font-bold">Ajouter un document</h2>
                    <form method="post" action="{{ route('suppliers.documents.store', $supplier) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="text" name="title" placeholder="Titre" class="w-full rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        <select name="category" class="w-full rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                            <option value="contract">Contrat</option>
                            <option value="certificate">Attestation</option>
                            <option value="other">Autre</option>
                        </select>
                        <input type="file" name="document" required class="block w-full text-sm text-gp-muted">
                        <button class="gp-btn-primary w-full">Uploader</button>
                    </form>
                </aside>
            @endcan
        </section>
    @endif

    @if($tab === 'history')
        <section class="gp-card">
            <ol class="space-y-4 border-l border-gp-border pl-4 dark:border-white/10">
                @forelse($supplier->changeLogs as $log)
                    <li>
                        <p class="font-semibold">{{ $log->message }}</p>
                        <p class="text-xs text-gp-muted">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->user?->name ?: 'Système' }}</p>
                    </li>
                @empty
                    <li class="text-sm text-gp-muted">Aucun historique.</li>
                @endforelse
            </ol>
        </section>
    @endif
@endsection
