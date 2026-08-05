@extends('layouts.app')

@section('title', $sale->number)
@section('breadcrumb', 'Ventes / POS / Tickets')
@section('heading', $sale->number)
@section('subtitle', 'Détail du ticket · '.$sale->statusLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('pos.tickets.index') }}" class="gp-btn-secondary">Retour</a>
        @can('pos.reprint')
            @if($sale->status === 'completed')
                <a href="{{ route('pos.tickets.print', $sale) }}" target="_blank" class="gp-btn-secondary">Réimprimer</a>
            @endif
        @endcan
        @can('pos.cancel')
            @if(in_array($sale->status, ['completed', 'held'], true))
                <form method="post" action="{{ route('pos.tickets.cancel', $sale) }}" onsubmit="return confirm('Annuler ce ticket ? Le stock sera rétabli si la vente était validée.')">
                    @csrf
                    <button type="submit" class="gp-btn-secondary text-rose-600">Annuler</button>
                </form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    @include('pos._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-100">{{ session('success') }}</div>
    @endif

    <section class="mb-6 grid gap-4 lg:grid-cols-3">
        <article class="gp-card lg:col-span-2">
            <h2 class="mb-4 text-sm font-bold">Lignes</h2>
            <div class="overflow-x-auto">
                <table class="gp-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-right">Qté</th>
                            <th class="text-right">P.U.</th>
                            <th class="text-right">Remise</th>
                            <th class="text-right">TVA</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->lines as $line)
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $line->product_name }}</p>
                                    <p class="text-xs text-gp-muted">{{ $line->sku }}</p>
                                </td>
                                <td class="text-right">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($line->discount_percent, 0) }}%</td>
                                <td class="text-right">{{ number_format($line->tax_rate, 0) }}%</td>
                                <td class="text-right font-bold">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="space-y-4">
            <article class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Totaux</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gp-muted">HT</dt><dd>{{ number_format($sale->subtotal_ht, 2, ',', ' ') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Remises</dt><dd>{{ number_format($sale->discount_total, 2, ',', ' ') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">TVA</dt><dd>{{ number_format($sale->tax_total, 2, ',', ' ') }}</dd></div>
                    <div class="flex justify-between border-t border-gp-border pt-2 text-base font-bold dark:border-white/10"><dt>TTC</dt><dd class="text-gp-primary">{{ number_format($sale->total_ttc, 2, ',', ' ') }} {{ $sale->currency }}</dd></div>
                </dl>
            </article>
            <article class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Infos</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-gp-muted">Statut</dt><dd><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $sale->statusColor() }}">{{ $sale->statusLabel() }}</span></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gp-muted">Client</dt><dd class="text-right">{{ $sale->customer?->name ?? 'Passage' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gp-muted">Caissier</dt><dd>{{ $sale->cashier?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gp-muted">Boutique</dt><dd>{{ $sale->store?->name }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gp-muted">Session</dt><dd>{{ $sale->session?->number ?? '—' }}</dd></div>
                    @if($sale->notes)
                        <div><dt class="mb-1 text-gp-muted">Notes</dt><dd>{{ $sale->notes }}</dd></div>
                    @endif
                </dl>
            </article>
            @if($sale->payments->isNotEmpty())
                <article class="gp-card">
                    <h2 class="mb-3 text-sm font-bold">Paiements</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach($sale->payments as $pay)
                            <li class="flex justify-between">
                                <span>{{ $pay->methodLabel() }}</span>
                                <span class="font-bold">{{ number_format($pay->amount, 2, ',', ' ') }}</span>
                            </li>
                            @if($pay->method === 'cash' && $pay->change_amount > 0)
                                <li class="flex justify-between text-gp-muted"><span>Monnaie</span><span>{{ number_format($pay->change_amount, 2, ',', ' ') }}</span></li>
                            @endif
                        @endforeach
                    </ul>
                </article>
            @endif
        </aside>
    </section>
@endsection
