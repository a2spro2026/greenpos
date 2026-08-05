@extends('layouts.app')

@section('title', $invoice->number)
@section('breadcrumb', 'Finance / Facturation')
@section('heading', $invoice->number)
@section('subtitle', $invoice->typeLabel().' · '.$invoice->statusLabel().' · '.$invoice->customer?->displayName())

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('invoices.index') }}" class="gp-btn-secondary">Retour</a>
        @if($invoice->isEditable())
            @can('invoices.update')
                <a href="{{ route('invoices.edit', $invoice) }}" class="gp-btn-secondary">Modifier</a>
            @endcan
        @endif
        @if($invoice->status === 'draft')
            @can('invoices.update')
                <form method="post" action="{{ route('invoices.issue', $invoice) }}">@csrf<button class="gp-btn-primary">Émettre</button></form>
            @endcan
        @endif
        @can('invoices.print')
            <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('invoices.pdf')
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="gp-btn-secondary">PDF</a>
        @endcan
        @can('invoices.send')
            @if($invoice->status !== 'draft' && $invoice->status !== 'cancelled')
                <form method="post" action="{{ route('invoices.send', $invoice) }}">@csrf<button class="gp-btn-secondary">Envoyer</button></form>
            @endif
        @endcan
        @can('invoices.create')
            @if($invoice->type === 'invoice' && !in_array($invoice->status, ['draft', 'cancelled'], true))
                <form method="post" action="{{ route('invoices.credit-note', $invoice) }}" onsubmit="return confirm('Créer un avoir pour cette facture ?')">@csrf<button class="gp-btn-secondary">Avoir</button></form>
            @endif
        @endcan
        @can('invoices.cancel')
            @if(!in_array($invoice->status, ['cancelled', 'paid', 'draft'], true))
                <form method="post" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm('Annuler cette facture ?')">@csrf<button class="gp-btn-secondary text-rose-600">Annuler</button></form>
            @endif
        @endcan
        @can('invoices.delete')
            @if($invoice->status === 'draft')
                <form method="post" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Supprimer ce brouillon ?')">@csrf @method('DELETE')<button class="gp-btn-secondary text-rose-600">Supprimer</button></form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    @include('invoices._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Total TTC</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Payé</p><p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($invoice->amount_paid, 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Reste à payer</p><p class="mt-2 text-2xl font-bold {{ $invoice->balance_due > 0 ? 'text-amber-600' : '' }}">{{ number_format($invoice->balance_due, 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Échéance</p><p class="mt-2 text-xl font-bold {{ $invoice->isOverdue() ? 'text-orange-600' : '' }}">{{ optional($invoice->due_at)->format('d/m/Y') ?: '—' }}</p></article>
    </section>

    @php
        $tabs = [
            'overview' => 'Informations',
            'products' => 'Produits',
            'payments' => 'Paiements',
            'history' => 'Historique',
            'documents' => 'Documents',
            'notes' => 'Notes',
        ];
    @endphp
    <nav class="mb-5 flex gap-2 overflow-x-auto pb-1">
        @foreach($tabs as $key => $label)
            <a href="{{ route('invoices.show', ['invoice' => $invoice, 'tab' => $key]) }}"
               class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'bg-gp-primary text-white' : 'bg-white text-gp-muted ring-1 ring-gp-border dark:bg-white/5 dark:ring-white/10' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if($tab === 'overview')
        <section class="grid gap-4 lg:grid-cols-2">
            <article class="gp-card text-sm">
                <h2 class="mb-3 text-sm font-bold">Informations générales</h2>
                <dl class="grid gap-2 sm:grid-cols-2">
                    <div><dt class="text-gp-muted">Client</dt><dd class="font-semibold">{{ $invoice->customer?->displayName() }}</dd></div>
                    <div><dt class="text-gp-muted">Boutique</dt><dd>{{ $invoice->store?->name }}</dd></div>
                    <div><dt class="text-gp-muted">Date</dt><dd>{{ optional($invoice->invoiced_at)->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gp-muted">Statut</dt><dd><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $invoice->statusColor() }}">{{ $invoice->statusLabel() }}</span></dd></div>
                    <div><dt class="text-gp-muted">Référence</dt><dd>{{ $invoice->reference ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Conditions</dt><dd>{{ $invoice->payment_terms ?: '—' }}</dd></div>
                    @if($invoice->parentInvoice)
                        <div class="sm:col-span-2"><dt class="text-gp-muted">Facture liée</dt><dd><a href="{{ route('invoices.show', $invoice->parentInvoice) }}" class="text-gp-primary hover:underline">{{ $invoice->parentInvoice->number }}</a></dd></div>
                    @endif
                    @if($invoice->posSale)
                        <div class="sm:col-span-2"><dt class="text-gp-muted">Ticket POS</dt><dd><a href="{{ route('pos.tickets.show', $invoice->posSale) }}" class="text-gp-primary hover:underline">{{ $invoice->posSale->number }}</a></dd></div>
                    @endif
                </dl>
            </article>
            <article class="gp-card text-sm">
                <h2 class="mb-3 text-sm font-bold">Client — coordonnées</h2>
                <p class="font-semibold">{{ $invoice->customer?->name }}</p>
                @if($invoice->customer?->company_name)<p>{{ $invoice->customer->company_name }}</p>@endif
                <p class="mt-2 text-gp-muted">{{ $invoice->customer?->address }}</p>
                <p class="text-gp-muted">{{ collect([$invoice->customer?->postal_code, $invoice->customer?->city, $invoice->customer?->country])->filter()->implode(', ') }}</p>
                <p class="mt-2">{{ $invoice->customer?->email }} · {{ $invoice->customer?->phone }}</p>
                @if($invoice->customer?->tax_id)<p class="mt-1 text-gp-muted">ICE : {{ $invoice->customer->tax_id }}</p>@endif
            </article>
        </section>
    @endif

    @if($tab === 'products')
        <section class="gp-card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left">Produit</th>
                            <th class="px-4 py-3 text-right">Qté</th>
                            <th class="px-4 py-3 text-right">P.U.</th>
                            <th class="px-4 py-3 text-right">Remise</th>
                            <th class="px-4 py-3 text-right">TVA</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($invoice->lines as $line)
                            <tr>
                                <td class="px-4 py-3"><p class="font-semibold">{{ $line->product_name }}</p><p class="text-xs text-gp-muted">{{ $line->sku }}</p></td>
                                <td class="px-4 py-3 text-right">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($line->discount_percent, 0) }}%</td>
                                <td class="px-4 py-3 text-right">{{ number_format($line->tax_rate, 0) }}%</td>
                                <td class="px-4 py-3 text-right font-bold">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-5 py-4 text-sm dark:border-white/10 sm:ml-auto sm:max-w-xs">
                <div class="flex justify-between"><span>HT</span><span>{{ number_format($invoice->subtotal_ht, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between"><span>TVA</span><span>{{ number_format($invoice->tax_total, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between border-t border-gp-border pt-2 font-bold dark:border-white/10"><span>TTC</span><span class="text-gp-primary">{{ number_format($invoice->total_ttc, 2, ',', ' ') }}</span></div>
            </div>
        </section>
    @endif

    @if($tab === 'payments')
        <section class="grid gap-4 lg:grid-cols-2">
            @if(in_array($invoice->status, ['pending', 'partial', 'expired'], true))
                @can('invoices.update')
                    <form method="post" action="{{ route('invoices.payments.store', $invoice) }}" class="gp-card space-y-3">
                        @csrf
                        <h2 class="text-sm font-bold">Enregistrer un paiement</h2>
                        <label class="block text-sm">
                            <span class="mb-1 block font-semibold">Montant</span>
                            <input type="number" name="amount" step="0.01" max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-semibold">Mode</span>
                            <select name="method" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                                @foreach(\App\Models\InvoicePayment::METHODS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-semibold">Date</span>
                            <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-semibold">Référence</span>
                            <input type="text" name="reference" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                        </label>
                        <button class="gp-btn-primary">Valider le paiement</button>
                    </form>
                @endcan
            @endif
            <article class="gp-card {{ !in_array($invoice->status, ['pending', 'partial', 'expired'], true) ? 'lg:col-span-2' : '' }}">
                <h2 class="mb-3 text-sm font-bold">Paiements enregistrés</h2>
                @if($invoice->payments->isEmpty())
                    <p class="text-sm text-gp-muted">Aucun paiement pour le moment.</p>
                @else
                    <ul class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($invoice->payments as $pay)
                            <li class="flex items-center justify-between py-3 text-sm">
                                <div>
                                    <p class="font-semibold">{{ $pay->methodLabel() }}</p>
                                    <p class="text-xs text-gp-muted">{{ optional($pay->paid_at)->format('d/m/Y') }} · {{ $pay->reference ?: '—' }} · {{ $pay->creator?->name }}</p>
                                </div>
                                <span class="font-bold text-emerald-600">{{ number_format($pay->amount, 2, ',', ' ') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </section>
    @endif

    @if($tab === 'history')
        <section class="gp-card">
            <ol class="space-y-4 border-l border-gp-border pl-4 dark:border-white/10">
                @forelse($invoice->logs as $log)
                    <li>
                        <p class="text-xs text-gp-muted">{{ $log->created_at->format('d/m/Y H:i') }} · {{ $log->user?->name ?: 'Système' }}</p>
                        <p class="font-semibold">{{ $log->message }}</p>
                    </li>
                @empty
                    <li class="text-sm text-gp-muted">Aucun historique.</li>
                @endforelse
            </ol>
        </section>
    @endif

    @if($tab === 'documents')
        <section class="gp-card">
            <div class="flex flex-wrap gap-3">
                @can('invoices.pdf')
                    <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="gp-shortcut max-w-xs">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gp-primary-soft text-gp-primary"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8"/></svg></span>
                        <span><span class="block text-sm font-semibold">Télécharger PDF</span><span class="text-xs text-gp-muted">Modèle professionnel</span></span>
                    </a>
                @endcan
                @can('invoices.print')
                    <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="gp-shortcut max-w-xs">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gp-primary-soft text-gp-primary"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5"/></svg></span>
                        <span><span class="block text-sm font-semibold">Imprimer</span><span class="text-xs text-gp-muted">Version papier</span></span>
                    </a>
                @endcan
            </div>
            @if($invoice->creditNotes->isNotEmpty())
                <h3 class="mb-2 mt-6 text-sm font-bold">Avoirs liés</h3>
                <ul class="space-y-2 text-sm">
                    @foreach($invoice->creditNotes as $cn)
                        <li><a href="{{ route('invoices.show', $cn) }}" class="font-semibold text-gp-primary hover:underline">{{ $cn->number }}</a> · {{ number_format($cn->total_ttc, 2, ',', ' ') }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if($tab === 'notes')
        <section class="gp-card space-y-4 text-sm">
            <div>
                <h2 class="mb-2 text-sm font-bold">Notes internes</h2>
                <p class="text-gp-muted">{{ $invoice->notes ?: 'Aucune note interne.' }}</p>
            </div>
            <div>
                <h2 class="mb-2 text-sm font-bold">Notes client (sur facture)</h2>
                <p class="text-gp-muted">{{ $invoice->customer_notes ?: 'Aucune note client.' }}</p>
            </div>
        </section>
    @endif
@endsection
