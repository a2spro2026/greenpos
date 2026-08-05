@extends('layouts.app')

@section('title', 'Mouvement #'.$movement->id)
@section('breadcrumb', 'Catalogue / Stock / Mouvements')
@section('heading', 'Mouvement #'.$movement->id)
@section('subtitle', $movement->typeLabel().' — '.$movement->product?->name)

@section('actions')
    <a href="{{ route('stock.movements.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('stock._nav')

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="gp-card lg:col-span-2 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Produit</p><p class="mt-1 font-bold">{{ $movement->product?->name }}</p><p class="text-xs text-gp-muted">{{ $movement->product?->sku }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Boutique</p><p class="mt-1 font-bold">{{ $movement->store?->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Type</p><p class="mt-1"><span class="gp-badge">{{ $movement->typeLabel() }}</span></p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Quantité</p><p class="mt-1 font-bold">{{ number_format($movement->quantity, 3, ',', ' ') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Avant</p><p class="mt-1 font-mono">{{ number_format($movement->quantity_before, 3, ',', ' ') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Après</p><p class="mt-1 font-mono">{{ number_format($movement->quantity_after, 3, ',', ' ') }}</p></div>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gp-muted">Commentaire</p>
                <p class="mt-1 text-sm">{{ $movement->comment ?: '—' }}</p>
            </div>
        </article>
        <aside class="gp-card space-y-3">
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Date</p><p class="mt-1 font-semibold">{{ $movement->moved_at?->format('d/m/Y H:i') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Référence</p><p class="mt-1">{{ $movement->reference ?: '—' }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Utilisateur</p><p class="mt-1">{{ $movement->user?->name ?: '—' }}</p></div>
            @if($movement->inventory_id)
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Inventaire</p><p class="mt-1"><a class="text-gp-primary hover:underline" href="{{ route('stock.inventories.show', $movement->inventory_id) }}">#{{ $movement->inventory_id }}</a></p></div>
            @endif
        </aside>
    </section>
@endsection
