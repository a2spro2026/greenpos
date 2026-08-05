@extends('layouts.app')

@section('title', $sale->number)
@section('breadcrumb', 'Ventes / ' . $sale->number)
@section('heading', $sale->number)
@section('subtitle', $sale->originLabel() . ' — ' . $sale->statusLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        @if($sale->isEditable())
            <a href="{{ route('sales.edit', $sale) }}" class="gp-btn-secondary">Modifier</a>
            <form method="POST" action="{{ route('sales.confirm', $sale) }}" class="inline">@csrf<button class="gp-btn-primary !bg-emerald-600">Confirmer</button></form>
            <form method="POST" action="{{ route('sales.destroy', $sale) }}" class="inline" onsubmit="return confirm('Supprimer ce brouillon ?')">@csrf @method('DELETE')<button class="gp-btn-secondary !text-rose-600">Supprimer</button></form>
        @endif

        @if(in_array($sale->status, ['confirmed','preparing','delivered']))
            @php
                $next = match($sale->status) {
                    'confirmed' => ['preparing' => 'En préparation', 'delivered' => 'Livrée', 'completed' => 'Terminée'],
                    'preparing' => ['delivered' => 'Livrée', 'completed' => 'Terminée'],
                    'delivered' => ['completed' => 'Terminée'],
                    default => [],
                };
            @endphp
            @foreach($next as $k => $v)
                <form method="POST" action="{{ route('sales.advance', $sale) }}" class="inline">@csrf<input type="hidden" name="target" value="{{ $k }}"><button class="gp-btn-secondary">{{ $v }}</button></form>
            @endforeach
        @endif

        @can('sales.cancel')
            @if(!in_array($sale->status, ['cancelled','returned']))
                <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="inline" onsubmit="return confirm('Annuler cette vente ?')">@csrf<button class="gp-btn-secondary !text-rose-600">Annuler</button></form>
            @endif
        @endcan

        @can('sales.return')
            @if(in_array($sale->status, ['confirmed','delivered','completed']))
                <a href="{{ route('sales.return', $sale) }}" class="gp-btn-secondary !text-orange-600">Retour</a>
            @endif
        @endcan

        @can('sales.print')
            <a href="{{ route('sales.print', $sale) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('sales._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    {{-- Tabs --}}
    @php
        $tabs = [
            'overview' => 'Informations',
            'products' => 'Produits',
            'payments' => 'Paiements',
            'returns' => 'Retours',
            'invoice' => 'Facture',
            'history' => 'Historique',
            'documents' => 'Documents',
        ];
    @endphp
    <nav class="mb-6 flex gap-1 overflow-x-auto border-b border-gp-border dark:border-white/10">
        @foreach($tabs as $key => $label)
            <a href="{{ route('sales.show', ['sale' => $sale, 'tab' => $key]) }}"
               class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold transition {{ $tab === $key ? 'border-gp-primary text-gp-primary' : 'border-transparent text-gp-muted hover:text-gp-text' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    {{-- TAB: Overview --}}
    @if($tab === 'overview')
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Informations générales</h2>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-gp-muted">Référence</dt><dd class="font-semibold">{{ $sale->number }}</dd></div>
                    <div><dt class="text-gp-muted">Date</dt><dd class="font-semibold">{{ optional($sale->sold_at)->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gp-muted">Client</dt><dd class="font-semibold">{{ $sale->customer?->name ?? 'Passage' }}</dd></div>
                    <div><dt class="text-gp-muted">Boutique</dt><dd class="font-semibold">{{ $sale->store?->name }}</dd></div>
                    <div><dt class="text-gp-muted">Commercial</dt><dd class="font-semibold">{{ $sale->salesperson?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Origine</dt><dd><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold dark:bg-white/10">{{ $sale->originLabel() }}</span></dd></div>
                    <div><dt class="text-gp-muted">Statut</dt><dd><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $sale->statusColor() }}">{{ $sale->statusLabel() }}</span></dd></div>
                    <div><dt class="text-gp-muted">Devise</dt><dd class="font-semibold">{{ $sale->currency }}</dd></div>
                    @if($sale->reference)<div class="sm:col-span-2"><dt class="text-gp-muted">Réf externe</dt><dd class="font-semibold">{{ $sale->reference }}</dd></div>@endif
                    @if($sale->notes)<div class="sm:col-span-2"><dt class="text-gp-muted">Notes</dt><dd>{{ $sale->notes }}</dd></div>@endif
                </dl>
            </article>
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Résumé financier</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gp-muted">Total HT</dt><dd class="font-bold">{{ number_format($sale->subtotal_ht, 2, ',', ' ') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">TVA</dt><dd class="font-bold">{{ number_format($sale->tax_total, 2, ',', ' ') }}</dd></div>
                    @if($sale->discount_total > 0)<div class="flex justify-between"><dt class="text-gp-muted">Remise</dt><dd class="font-bold text-rose-600">-{{ number_format($sale->discount_total, 2, ',', ' ') }}</dd></div>@endif
                    <div class="flex justify-between border-t pt-2"><dt class="font-bold">Total TTC</dt><dd class="text-lg font-bold text-gp-primary">{{ number_format($sale->total_ttc, 2, ',', ' ') }} {{ $sale->currency }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Payé</dt><dd class="font-bold text-emerald-600">{{ number_format($sale->amount_paid, 2, ',', ' ') }}</dd></div>
                    @if($sale->amount_returned > 0)<div class="flex justify-between"><dt class="text-gp-muted">Retourné</dt><dd class="font-bold text-orange-600">{{ number_format($sale->amount_returned, 2, ',', ' ') }}</dd></div>@endif
                    @if($sale->balanceDue() > 0)<div class="flex justify-between"><dt class="text-gp-muted">Reste à payer</dt><dd class="font-bold text-amber-600">{{ number_format($sale->balanceDue(), 2, ',', ' ') }}</dd></div>@endif
                </dl>

                @if($sale->posSale)<p class="mt-4 text-xs text-gp-muted">Ticket POS : <a href="{{ route('pos.tickets.show', $sale->posSale) }}" class="text-gp-primary hover:underline">{{ $sale->posSale->number }}</a></p>@endif
                @if($sale->quote)<p class="mt-2 text-xs text-gp-muted">Devis : <a href="{{ route('quotes.show', $sale->quote) }}" class="text-gp-primary hover:underline">{{ $sale->quote->number }}</a></p>@endif
                @if($sale->invoice)<p class="mt-2 text-xs text-gp-muted">Facture : <a href="{{ route('invoices.show', $sale->invoice) }}" class="text-gp-primary hover:underline">{{ $sale->invoice->number }}</a></p>@endif
            </article>
        </div>
    @endif

    {{-- TAB: Products --}}
    @if($tab === 'products')
        <section class="gp-card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Produit</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3 text-right">Qté</th>
                            <th class="px-4 py-3 text-right">Prix unit.</th>
                            <th class="px-4 py-3 text-right">Remise</th>
                            <th class="px-4 py-3 text-right">TVA</th>
                            <th class="px-4 py-3 text-right">Sous-total</th>
                            <th class="px-4 py-3 text-right">Total TTC</th>
                            <th class="px-4 py-3 text-right">Retourné</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($sale->lines as $line)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $line->product_name }}</td>
                                <td class="px-4 py-3 text-gp-muted">{{ $line->sku ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $line->quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">{{ $line->discount_percent > 0 ? $line->discount_percent.'%' : '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $line->tax_rate }}%</td>
                                <td class="px-4 py-3 text-right">{{ number_format($line->line_subtotal, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right {{ $line->returned_quantity > 0 ? 'text-orange-600 font-bold' : 'text-gp-muted' }}">{{ $line->returned_quantity > 0 ? $line->returned_quantity : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- TAB: Payments --}}
    @if($tab === 'payments')
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="gp-card overflow-hidden p-0 lg:col-span-2">
                <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Historique des paiements</h2></div>
                @if($sale->payments->isEmpty())
                    <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucun paiement enregistré.</div>
                @else
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                            <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Mode</th><th class="px-4 py-3 text-right">Montant</th><th class="px-4 py-3">Réf</th><th class="px-4 py-3">Par</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gp-border dark:divide-white/10">
                            @foreach($sale->payments as $p)
                                <tr>
                                    <td class="px-4 py-3">{{ optional($p->paid_at)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">{{ $p->methodLabel() }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ number_format($p->amount, 2, ',', ' ') }}</td>
                                    <td class="px-4 py-3 text-gp-muted">{{ $p->reference ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gp-muted">{{ $p->creator?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            @if($sale->balanceDue() > 0 && !in_array($sale->status, ['cancelled','returned','draft']))
                <section class="gp-card">
                    <h2 class="mb-4 text-sm font-bold">Enregistrer un paiement</h2>
                    <form method="POST" action="{{ route('sales.payments.store', $sale) }}" class="space-y-3">
                        @csrf
                        <div><label class="gp-label">Mode</label><select name="method" class="gp-select w-full">@foreach(\App\Models\SalePayment::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                        <div><label class="gp-label">Montant *</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $sale->balanceDue() }}" value="{{ $sale->balanceDue() }}" class="gp-input w-full" required></div>
                        <div><label class="gp-label">Date</label><input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" class="gp-input w-full"></div>
                        <div><label class="gp-label">Référence</label><input type="text" name="reference" class="gp-input w-full" placeholder="N° chèque, virement…"></div>
                        <button class="gp-btn-primary w-full">Enregistrer</button>
                    </form>
                </section>
            @endif
        </div>
    @endif

    {{-- TAB: Returns --}}
    @if($tab === 'returns')
        <section class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10">
                <h2 class="text-sm font-bold">Retours</h2>
            </div>
            @if($sale->returns->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucun retour enregistré.</div>
            @else
                @foreach($sale->returns as $ret)
                    <div class="border-b border-gp-border px-5 py-4 dark:border-white/10 last:border-b-0">
                        <div class="mb-2 flex items-center justify-between">
                            <div>
                                <span class="font-bold">{{ $ret->number }}</span>
                                <span class="ml-2 inline-flex rounded-full {{ $ret->type === 'total' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }} px-2.5 py-0.5 text-xs font-semibold">{{ $ret->type === 'total' ? 'Total' : 'Partiel' }}</span>
                            </div>
                            <span class="text-sm text-gp-muted">{{ optional($ret->returned_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="mb-2 text-sm"><strong>Motif :</strong> {{ $ret->reason }}</p>
                        @if($ret->notes)<p class="mb-2 text-sm text-gp-muted">{{ $ret->notes }}</p>@endif
                        <p class="text-sm">Montant retourné : <strong class="text-orange-600">{{ number_format($ret->total_returned, 2, ',', ' ') }} MAD</strong> — Restock : {{ $ret->restock ? 'Oui' : 'Non' }}</p>
                        @if($ret->returnLines->isNotEmpty())
                            <table class="mt-2 w-full text-sm">
                                <thead class="text-xs uppercase text-gp-muted"><tr><th class="py-1 text-left">Produit</th><th class="py-1 text-right">Qté</th><th class="py-1 text-right">Remboursement</th></tr></thead>
                                <tbody>
                                    @foreach($ret->returnLines as $rl)
                                        <tr><td class="py-1">{{ $rl->saleLine?->product_name ?? '—' }}</td><td class="py-1 text-right">{{ $rl->quantity }}</td><td class="py-1 text-right font-bold">{{ number_format($rl->line_refund, 2, ',', ' ') }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endforeach
            @endif
        </section>
    @endif

    {{-- TAB: Invoice --}}
    @if($tab === 'invoice')
        <section class="gp-card">
            @if($sale->invoice)
                <h2 class="mb-4 text-sm font-bold">Facture liée</h2>
                <p><a href="{{ route('invoices.show', $sale->invoice) }}" class="text-gp-primary font-semibold hover:underline">{{ $sale->invoice->number }}</a> — <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $sale->invoice->statusColor() }}">{{ $sale->invoice->statusLabel() }}</span></p>
                <p class="mt-2 text-sm text-gp-muted">Total : {{ number_format($sale->invoice->total_ttc, 2, ',', ' ') }} {{ $sale->invoice->currency }}</p>
            @else
                <p class="py-8 text-center text-gp-muted">Aucune facture liée à cette vente.</p>
            @endif
        </section>
    @endif

    {{-- TAB: History --}}
    @if($tab === 'history')
        <section class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Journal d'activité</h2></div>
            @if($sale->logs->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune entrée.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($sale->logs as $log)
                        <li class="flex items-start gap-3 px-5 py-3 text-sm">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold dark:bg-white/10">{{ strtoupper(substr($log->action, 0, 2)) }}</div>
                            <div class="flex-1">
                                <p>{{ $log->message }}</p>
                                <p class="text-xs text-gp-muted">{{ $log->user?->name ?? 'Système' }} · {{ $log->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    {{-- TAB: Documents --}}
    @if($tab === 'documents')
        <section class="gp-card">
            <div class="py-8 text-center text-gp-muted">Les pièces jointes seront disponibles prochainement.</div>
        </section>
    @endif
@endsection
