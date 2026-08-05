@extends('layouts.app')

@section('title', 'Réceptions')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Réceptions')
@section('subtitle', 'Marchandises reçues et validations stock.')

@section('content')
    @include('purchases._nav')

    <section class="gp-card overflow-hidden p-0">
        @if($receipts->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucune réception</p>
                <p class="mt-2 text-sm text-gp-muted">Les réceptions apparaîtront après validation d’un bon de commande.</p>
                <a href="{{ route('purchases.orders.index') }}" class="gp-btn-primary mt-5">Voir les commandes</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">N°</th>
                            <th class="px-4 py-3">Commande</th>
                            <th class="px-4 py-3">Fournisseur</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Par</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($receipts as $receipt)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('purchases.receipts.show', $receipt) }}" class="hover:text-gp-primary">{{ $receipt->number }}</a></td>
                                <td class="px-4 py-3"><a href="{{ route('purchases.orders.show', $receipt->purchase_order_id) }}" class="hover:underline">{{ $receipt->order?->number }}</a></td>
                                <td class="px-4 py-3">{{ $receipt->order?->supplier?->name }}</td>
                                <td class="px-4 py-3">{{ $receipt->store?->name }}</td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ optional($receipt->received_at)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3"><span class="gp-badge">{{ $receipt->statusLabel() }}</span></td>
                                <td class="px-4 py-3 text-gp-muted">{{ $receipt->receiver?->name ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $receipts->links() }}</div>
        @endif
    </section>
@endsection
