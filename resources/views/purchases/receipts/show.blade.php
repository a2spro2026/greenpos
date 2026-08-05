@extends('layouts.app')

@section('title', $receipt->number)
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', $receipt->number)
@section('subtitle', 'Réception liée à '.$receipt->order?->number)

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('purchases.orders.show', $receipt->purchase_order_id) }}" class="gp-btn-secondary">Voir la commande</a>
        @if($receipt->status === 'draft')
            @can('purchases.receive')
                <form method="post" action="{{ route('purchases.receipts.validate', $receipt) }}" onsubmit="return confirm('Valider et mettre à jour le stock ?')">
                    @csrf
                    <button class="gp-btn-primary">Valider la réception</button>
                </form>
            @endcan
        @endif
    </div>
@endsection

@section('content')
    @include('purchases._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="mb-4 grid gap-4 lg:grid-cols-3">
        <article class="gp-card lg:col-span-2">
            <div class="mb-4 flex flex-wrap gap-3 text-sm">
                <span class="gp-badge">{{ $receipt->statusLabel() }}</span>
                <span>{{ optional($receipt->received_at)->format('d/m/Y') }}</span>
                <span class="text-gp-muted">{{ $receipt->store?->name }}</span>
                <span class="text-gp-muted">{{ $receipt->receiver?->name }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-gp-muted">
                        <tr>
                            <th class="px-3 py-2 text-left">Produit</th>
                            <th class="px-3 py-2 text-right">Commandé</th>
                            <th class="px-3 py-2 text-right">Déjà reçu</th>
                            <th class="px-3 py-2 text-right">Cette réception</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($receipt->lines as $line)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $line->product?->name }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->ordered_qty, 3, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->previously_received, 3, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right font-bold text-gp-primary">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
        <aside class="gp-card text-sm">
            <p class="text-xs font-semibold uppercase text-gp-muted">Fournisseur</p>
            <p class="mt-1 font-bold">{{ $receipt->order?->supplier?->name }}</p>
            <p class="mt-4 text-xs font-semibold uppercase text-gp-muted">Notes</p>
            <p class="mt-1">{{ $receipt->notes ?: '—' }}</p>
            @if($receipt->validated_at)
                <p class="mt-4 text-xs text-gp-muted">Validée le {{ $receipt->validated_at->format('d/m/Y H:i') }}</p>
            @endif
        </aside>
    </section>
@endsection
