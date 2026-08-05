@extends('layouts.app')

@section('title', 'Mouvements de stock')
@section('breadcrumb', 'Catalogue / Stock / Mouvements')
@section('heading', 'Mouvements de stock')
@section('subtitle', 'Historique complet des entrées, sorties et ajustements.')

@section('actions')
    @can('stock.move')
        <a href="{{ route('stock.movements.create') }}" class="gp-btn-primary">Nouveau mouvement</a>
    @endcan
@endsection

@section('content')
    @include('stock._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Référence, produit, commentaire…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="type" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous types</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="store_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Toutes boutiques</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($movements->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucun mouvement</p>
                <p class="mt-2 text-sm text-gp-muted">Les mouvements apparaîtront ici dès la première entrée.</p>
                @can('stock.move')
                    <a href="{{ route('stock.movements.create') }}" class="gp-btn-primary mt-5">Créer un mouvement</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Produit</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Quantité</th>
                            <th class="px-4 py-3">Avant → Après</th>
                            <th class="px-4 py-3">Référence</th>
                            <th class="px-4 py-3">Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($movements as $movement)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $movement->moved_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-semibold"><a class="hover:text-gp-primary" href="{{ route('stock.movements.show', $movement) }}">{{ $movement->product?->name }}</a></td>
                                <td class="px-4 py-3">{{ $movement->store?->name }}</td>
                                <td class="px-4 py-3"><span class="gp-badge">{{ $movement->typeLabel() }}</span></td>
                                <td class="px-4 py-3 font-mono text-xs">{{ number_format($movement->quantity, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gp-muted">{{ number_format($movement->quantity_before, 3, ',', ' ') }} → {{ number_format($movement->quantity_after, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-xs">{{ $movement->reference ?: '—' }}</td>
                                <td class="px-4 py-3 text-gp-muted">{{ $movement->user?->name ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $movements->links() }}</div>
        @endif
    </section>
@endsection
