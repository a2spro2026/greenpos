@extends('layouts.app')

@section('title', $order->number)
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', $order->number)
@section('subtitle', $order->supplier?->name.' · '.$order->store?->name)

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('purchases.print')
            <a href="{{ route('purchases.orders.print', $order) }}" target="_blank" class="gp-btn-secondary">Imprimer / PDF</a>
        @endcan
        @if($order->isEditable())
            @can('purchases.update')
                <a href="{{ route('purchases.orders.edit', $order) }}" class="gp-btn-secondary">Modifier</a>
                <form method="post" action="{{ route('purchases.orders.send', $order) }}">@csrf<button class="gp-btn-secondary">Envoyer</button></form>
                <form method="post" action="{{ route('purchases.orders.confirm', $order) }}">@csrf<button class="gp-btn-primary">Confirmer</button></form>
            @endcan
        @endif
        @if($order->status === 'sent')
            @can('purchases.update')
                <form method="post" action="{{ route('purchases.orders.confirm', $order) }}">@csrf<button class="gp-btn-primary">Confirmer</button></form>
            @endcan
        @endif
        @if($order->canReceive())
            @can('purchases.receive')
                <a href="{{ route('purchases.receipts.create', $order) }}" class="gp-btn-primary">Réceptionner</a>
            @endcan
        @endif
        @if(! in_array($order->status, ['received', 'cancelled'], true))
            @can('purchases.cancel')
                <form method="post" action="{{ route('purchases.orders.cancel', $order) }}" onsubmit="return confirm('Annuler cette commande ?')">@csrf<button class="gp-btn-secondary text-rose-600">Annuler</button></form>
            @endcan
        @endif
    </div>
@endsection

@section('content')
    @include('purchases._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    <section class="mb-4 grid gap-4 lg:grid-cols-3">
        <article class="gp-card lg:col-span-2 space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="gp-badge {{ $order->statusColor() }}">{{ $order->statusLabel() }}</span>
                <span class="text-xs text-gp-muted">Créé par {{ $order->creator?->name ?: '—' }}</span>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Fournisseur</p><p class="mt-1 font-bold">{{ $order->supplier?->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Boutique</p><p class="mt-1 font-bold">{{ $order->store?->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Date</p><p class="mt-1 font-bold">{{ optional($order->ordered_at)->format('d/m/Y') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Référence</p><p class="mt-1">{{ $order->reference ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Devise</p><p class="mt-1">{{ $order->currency }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Livraison prévue</p><p class="mt-1">{{ optional($order->expected_at)->format('d/m/Y') ?: '—' }}</p></div>
            </div>
            @if($order->notes)
                <p class="text-sm text-gp-muted">{{ $order->notes }}</p>
            @endif

            <div class="overflow-x-auto rounded-xl border border-gp-border dark:border-white/10">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-gp-muted dark:bg-white/5">
                        <tr>
                            <th class="px-3 py-2 text-left">Produit</th>
                            <th class="px-3 py-2 text-right">Qté</th>
                            <th class="px-3 py-2 text-right">Reçu</th>
                            <th class="px-3 py-2 text-right">Prix</th>
                            <th class="px-3 py-2 text-right">Remise</th>
                            <th class="px-3 py-2 text-right">TVA</th>
                            <th class="px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($order->lines as $line)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $line->product?->name }}<div class="font-mono text-xs text-gp-muted">{{ $line->product?->sku }}</div></td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->received_quantity, 3, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->discount_percent, 1, ',', ' ') }}%</td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->tax_rate, 1, ',', ' ') }}%</td>
                                <td class="px-3 py-2 text-right font-bold">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="space-y-4">
            <article class="gp-card space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gp-muted">Total HT</span><span class="font-bold">{{ number_format($order->subtotal_ht, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between"><span class="text-gp-muted">Remises</span><span>{{ number_format($order->discount_total, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between"><span class="text-gp-muted">TVA</span><span class="font-bold">{{ number_format($order->tax_total, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between border-t border-gp-border pt-2 text-base dark:border-white/10"><span class="font-semibold">Total TTC</span><span class="font-bold text-gp-primary">{{ number_format($order->total_ttc, 2, ',', ' ') }} {{ $order->currency }}</span></div>
            </article>

            <article class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Réceptions</h2>
                @forelse($order->receipts as $receipt)
                    <a href="{{ route('purchases.receipts.show', $receipt) }}" class="mb-2 block rounded-xl border border-gp-border px-3 py-2 text-sm hover:border-gp-primary dark:border-white/10">
                        <span class="font-semibold">{{ $receipt->number }}</span>
                        <span class="text-xs text-gp-muted"> · {{ $receipt->statusLabel() }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gp-muted">Aucune réception.</p>
                @endforelse
            </article>

            <article class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Historique</h2>
                <ol class="space-y-3 border-l border-gp-border pl-4 dark:border-white/10">
                    @forelse($order->logs->take(10) as $log)
                        <li>
                            <p class="text-sm font-semibold">{{ $log->message }}</p>
                            <p class="text-xs text-gp-muted">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->user?->name ?: 'Système' }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-gp-muted">Aucun événement.</li>
                    @endforelse
                </ol>
            </article>
        </aside>
    </section>
@endsection
