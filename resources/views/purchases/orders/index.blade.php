@extends('layouts.app')

@section('title', 'Commandes d’achat')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Bons de commande')
@section('subtitle', 'Suivi des commandes fournisseurs.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('purchases.export')
            <a href="{{ route('purchases.orders.export', request()->query()) }}" class="gp-btn-secondary">Exporter Excel/CSV</a>
        @endcan
        @can('purchases.create')
            <a href="{{ route('purchases.orders.create') }}" class="gp-btn-primary">Nouveau BC</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('purchases._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N°, référence, fournisseur…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="status" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous statuts</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="store_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Boutique</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="supplier_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Fournisseur</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($orders->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucune commande</p>
                <p class="mt-2 text-sm text-gp-muted">Créez un bon de commande pour démarrer l’approvisionnement.</p>
                @can('purchases.create')
                    <a href="{{ route('purchases.orders.create') }}" class="gp-btn-primary mt-5">Créer un BC</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">N° commande</th>
                            <th class="px-4 py-3">Fournisseur</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Montant</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Utilisateur</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('purchases.orders.show', $order) }}" class="hover:text-gp-primary">{{ $order->number }}</a></td>
                                <td class="px-4 py-3">{{ $order->supplier?->name }}</td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ optional($order->ordered_at)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ $order->store?->name }}</td>
                                <td class="px-4 py-3 font-bold">{{ number_format($order->total_ttc, 2, ',', ' ') }} {{ $order->currency }}</td>
                                <td class="px-4 py-3"><span class="gp-badge {{ $order->statusColor() }}">{{ $order->statusLabel() }}</span></td>
                                <td class="px-4 py-3 text-gp-muted">{{ $order->creator?->name ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                        <a href="{{ route('purchases.orders.show', $order) }}" class="text-gp-primary hover:underline">Voir</a>
                                        @can('purchases.print')
                                            <a href="{{ route('purchases.orders.print', $order) }}" target="_blank" class="text-gp-muted hover:underline">Imprimer</a>
                                        @endcan
                                        @if($order->canReceive())
                                            @can('purchases.receive')
                                                <a href="{{ route('purchases.receipts.create', $order) }}" class="text-emerald-600 hover:underline">Réceptionner</a>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection
