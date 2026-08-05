@extends('layouts.app')

@section('title', $quote->number)
@section('breadcrumb', 'Ventes / Devis')
@section('heading', $quote->number)
@section('subtitle', $quote->statusLabel().' · '.$quote->customer?->displayName())

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('quotes.index') }}" class="gp-btn-secondary">Retour</a>
        @if($quote->isEditable())
            @can('quotes.update')
                <a href="{{ route('quotes.edit', $quote) }}" class="gp-btn-secondary">Modifier</a>
            @endcan
        @endif
        @can('quotes.create')
            <form method="post" action="{{ route('quotes.duplicate', $quote) }}">@csrf<button class="gp-btn-secondary">Dupliquer</button></form>
        @endcan
        @can('quotes.send')
            @if(in_array($quote->status, ['draft', 'pending'], true))
                <form method="post" action="{{ route('quotes.send', $quote) }}">@csrf<button class="gp-btn-secondary">Envoyer</button></form>
            @endif
        @endcan
        @can('quotes.print')
            <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" class="gp-btn-secondary">PDF</a>
            <a href="{{ route('quotes.print', $quote) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('quotes.delete')
            @if($quote->status === 'draft')
                <form method="post" action="{{ route('quotes.destroy', $quote) }}" onsubmit="return confirm('Supprimer ce brouillon ?')">@csrf @method('DELETE')<button class="gp-btn-secondary text-rose-600">Supprimer</button></form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    @include('quotes._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    @if($quote->isConvertible())
        <section class="mb-6 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-5 dark:border-emerald-500/20 dark:from-emerald-500/10 dark:to-teal-500/10">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">Actions de conversion</p>
            <p class="mt-1 text-sm text-gp-muted">Transformez ce devis en facture ou en vente POS en un clic.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                @can('quotes.convert')
                    <form method="post" action="{{ route('quotes.convert-invoice', $quote) }}" onsubmit="return confirm('Convertir ce devis en facture ?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-emerald-600/30 hover:bg-emerald-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/></svg>
                            Convertir en Facture
                        </button>
                    </form>
                    <form method="post" action="{{ route('quotes.convert-sale', $quote) }}" onsubmit="return confirm('Convertir en vente POS ? Une caisse doit être ouverte.')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-sky-600/30 hover:bg-sky-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h8v8H3V3zm10 0h8v8h-8V3z"/></svg>
                            Convertir en Vente
                        </button>
                    </form>
                @endcan
                @can('quotes.update')
                    @if(in_array($quote->status, ['sent', 'pending'], true))
                        <form method="post" action="{{ route('quotes.accept', $quote) }}">@csrf<button class="gp-btn-secondary">Marquer accepté</button></form>
                        <form method="post" action="{{ route('quotes.refuse', $quote) }}">@csrf<button class="gp-btn-secondary text-rose-600">Refuser</button></form>
                    @endif
                @endcan
            </div>
        </section>
    @endif

    @if($quote->status === 'converted')
        <section class="mb-6 rounded-2xl border border-violet-200 bg-violet-50 px-5 py-4 text-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="font-bold text-violet-900 dark:text-violet-100">Devis converti</p>
            <p class="mt-1 text-gp-muted">
                @if($quote->convertedInvoice)
                    <a href="{{ route('invoices.show', $quote->convertedInvoice) }}" class="font-semibold text-gp-primary hover:underline">Facture {{ $quote->convertedInvoice->number }}</a>
                @endif
                @if($quote->convertedPosSale)
                    · <a href="{{ route('pos.tickets.show', $quote->convertedPosSale) }}" class="font-semibold text-gp-primary hover:underline">Vente {{ $quote->convertedPosSale->number }}</a>
                @endif
            </p>
        </section>
    @endif

    <section class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Total TTC</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($quote->total_ttc, 2, ',', ' ') }} {{ $quote->currency }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Validité</p><p class="mt-2 text-xl font-bold {{ $quote->isExpired() ? 'text-orange-600' : '' }}">{{ optional($quote->valid_until)->format('d/m/Y') ?: '—' }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Commercial</p><p class="mt-2 text-xl font-bold">{{ $quote->salesperson?->name ?? '—' }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Statut</p><p class="mt-2"><span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $quote->statusColor() }}">{{ $quote->statusLabel() }}</span></p></article>
    </section>

    @php
        $tabs = ['overview' => 'Informations', 'products' => 'Produits', 'history' => 'Historique', 'documents' => 'Documents', 'notes' => 'Notes'];
    @endphp
    <nav class="mb-5 flex gap-2 overflow-x-auto pb-1">
        @foreach($tabs as $key => $label)
            <a href="{{ route('quotes.show', ['quote' => $quote, 'tab' => $key]) }}"
               class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'bg-gp-primary text-white' : 'bg-white text-gp-muted ring-1 ring-gp-border dark:bg-white/5 dark:ring-white/10' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if($tab === 'overview')
        <section class="grid gap-4 lg:grid-cols-2">
            <article class="gp-card text-sm">
                <h2 class="mb-3 text-sm font-bold">Informations</h2>
                <dl class="grid gap-2 sm:grid-cols-2">
                    <div><dt class="text-gp-muted">Client</dt><dd class="font-semibold">{{ $quote->customer?->displayName() }}</dd></div>
                    <div><dt class="text-gp-muted">Boutique</dt><dd>{{ $quote->store?->name }}</dd></div>
                    <div><dt class="text-gp-muted">Date</dt><dd>{{ optional($quote->quoted_at)->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gp-muted">Référence</dt><dd>{{ $quote->reference ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gp-muted">Conditions</dt><dd>{{ $quote->terms ?: '—' }}</dd></div>
                </dl>
            </article>
            <article class="gp-card text-sm">
                <h2 class="mb-3 text-sm font-bold">Client</h2>
                <p class="font-semibold">{{ $quote->customer?->name }}</p>
                <p class="text-gp-muted">{{ $quote->customer?->email }} · {{ $quote->customer?->phone }}</p>
                <p class="mt-2 text-gp-muted">{{ collect([$quote->customer?->address, $quote->customer?->city, $quote->customer?->country])->filter()->implode(', ') }}</p>
            </article>
        </section>
    @endif

    @if($tab === 'products')
        <section class="gp-card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr><th class="px-4 py-3 text-left">Produit</th><th class="px-4 py-3 text-right">Qté</th><th class="px-4 py-3 text-right">P.U.</th><th class="px-4 py-3 text-right">Remise</th><th class="px-4 py-3 text-right">TVA</th><th class="px-4 py-3 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($quote->lines as $line)
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
                <div class="flex justify-between"><span>HT</span><span>{{ number_format($quote->subtotal_ht, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between"><span>TVA</span><span>{{ number_format($quote->tax_total, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between border-t border-gp-border pt-2 font-bold dark:border-white/10"><span>TTC</span><span class="text-gp-primary">{{ number_format($quote->total_ttc, 2, ',', ' ') }}</span></div>
            </div>
        </section>
    @endif

    @if($tab === 'history')
        <section class="gp-card">
            <ol class="space-y-4 border-l border-gp-border pl-4 dark:border-white/10">
                @forelse($quote->logs as $log)
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
                @can('quotes.print')
                    <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" class="gp-shortcut max-w-xs">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gp-primary-soft text-gp-primary"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"/></svg></span>
                        <span><span class="block text-sm font-semibold">Télécharger PDF</span><span class="text-xs text-gp-muted">Devis professionnel</span></span>
                    </a>
                    <a href="{{ route('quotes.print', $quote) }}" target="_blank" class="gp-shortcut max-w-xs">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gp-primary-soft text-gp-primary"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4"/></svg></span>
                        <span><span class="block text-sm font-semibold">Imprimer</span><span class="text-xs text-gp-muted">Version papier</span></span>
                    </a>
                @endcan
            </div>
        </section>
    @endif

    @if($tab === 'notes')
        <section class="gp-card space-y-4 text-sm">
            <div><h2 class="mb-2 text-sm font-bold">Notes internes</h2><p class="text-gp-muted">{{ $quote->notes ?: 'Aucune note.' }}</p></div>
            <div><h2 class="mb-2 text-sm font-bold">Notes client</h2><p class="text-gp-muted">{{ $quote->customer_notes ?: 'Aucune note client.' }}</p></div>
        </section>
    @endif
@endsection
