@extends('layouts.app')

@section('title', 'Comptabilité')
@section('breadcrumb', 'Finance')
@section('heading', 'Comptabilité')
@section('subtitle', 'Vue d’ensemble du mois en cours ('.$monthLabel.') et accès rapides.')

@php($fmt = fn ($v) => number_format((float) $v, 2, ',', ' ').' '.$currency)

@section('content')
    @include('accounting._nav')

    <section class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <a href="{{ route('accounting.cash.index') }}" class="gp-card block p-4 hover:ring-2 hover:ring-gp-primary">
            <p class="text-xs font-semibold uppercase text-gp-muted">Trésorerie — solde</p>
            <p class="mt-1 text-2xl font-bold">{{ $lastBalance !== null ? $fmt($lastBalance) : '—' }}</p>
            <p class="text-xs text-gp-muted">Entrées {{ $fmt($cashIn) }} · Sorties {{ $fmt($cashOut) }} ce mois</p>
        </a>
        <a href="{{ route('accounting.expenses.index') }}" class="gp-card block p-4 hover:ring-2 hover:ring-gp-primary">
            <p class="text-xs font-semibold uppercase text-gp-muted">Dépenses du mois</p>
            <p class="mt-1 text-2xl font-bold">{{ $fmt($expensesMonth) }}</p>
            <p class="text-xs text-gp-muted">{{ $expensesCount }} dépense(s) enregistrée(s)</p>
        </a>
        <a href="{{ route('accounting.cash.index') }}" class="gp-card block p-4 hover:ring-2 hover:ring-gp-primary">
            <p class="text-xs font-semibold uppercase text-gp-muted">Résultat de caisse du mois</p>
            <p class="mt-1 text-2xl font-bold {{ $cashIn - $cashOut < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $fmt($cashIn - $cashOut) }}</p>
            <p class="text-xs text-gp-muted">Entrées − sorties du journal de caisse</p>
        </a>
        <a href="{{ route('pos.collections') }}" class="gp-card block p-4 hover:ring-2 hover:ring-gp-primary">
            <p class="text-xs font-semibold uppercase text-gp-muted">Créances POS à encaisser</p>
            <p class="mt-1 text-2xl font-bold">{{ $fmt($receivablesTotal) }}</p>
            <p class="text-xs {{ $overdueCount ? 'font-semibold text-red-600' : 'text-gp-muted' }}">{{ $receivablesCount }} règlement(s) · {{ $overdueCount }} en retard</p>
        </a>
        <a href="{{ route('accounting.collections.index') }}" class="gp-card block p-4 hover:ring-2 hover:ring-gp-primary">
            <p class="text-xs font-semibold uppercase text-gp-muted">Factures clients ouvertes</p>
            <p class="mt-1 text-2xl font-bold">{{ $fmt($invoicesDue) }}</p>
            <p class="text-xs text-gp-muted">{{ $invoicesCount }} facture(s) avec un reste à payer</p>
        </a>
        <a href="{{ route('purchases.returns.index', ['status' => 'validated']) }}" class="gp-card block p-4 hover:ring-2 hover:ring-gp-primary">
            <p class="text-xs font-semibold uppercase text-gp-muted">Avoirs fournisseurs attendus</p>
            <p class="mt-1 text-2xl font-bold">{{ $fmt($pendingCreditsTotal) }}</p>
            <p class="text-xs text-gp-muted">{{ $pendingCreditsCount }} retour(s) validé(s) sans avoir</p>
        </a>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="gp-card lg:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold">Derniers mouvements de trésorerie</h2>
                <a href="{{ route('accounting.cash.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Tout voir</a>
            </div>
            @if($recent->isEmpty())
                <div class="py-8 text-center text-sm text-gp-muted">
                    Aucun mouvement pour l’instant. Les ventes encaissées, dépenses et mouvements de caisse alimentent automatiquement ce journal.
                    <div class="mt-3"><a href="{{ route('accounting.cash.create') }}" class="gp-btn-primary">Saisir un mouvement</a></div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-gp-border dark:divide-white/10">
                            @foreach($recent as $e)
                                <tr>
                                    <td class="py-2 pr-3 text-xs text-gp-muted">{{ optional($e->entry_date)->format('d/m/Y') }}</td>
                                    <td class="py-2 pr-3">{{ $e->label }}</td>
                                    <td class="py-2 text-right font-semibold {{ $e->direction === 'out' ? 'text-red-600' : 'text-emerald-600' }}">{{ $e->direction === 'out' ? '−' : '+' }}{{ number_format((float) $e->amount, 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
        <aside class="gp-card">
            <h2 class="mb-3 font-bold">Accès rapides</h2>
            <div class="grid gap-2 text-sm">
                @foreach([
                    ['accounting.ledger.index', 'Grand livre (écritures)'],
                    ['accounting.accounts.index', 'Plan de comptes'],
                    ['accounting.bilan', 'Bilan'],
                    ['accounting.vat', 'Déclaration / export TVA'],
                    ['accounting.expenses.create', 'Nouvelle dépense'],
                    ['accounting.cash.index', 'Trésorerie (caisse & banque)'],
                    ['pos.collections', 'Créances (chèques, effets, crédits)'],
                    ['purchases.returns.index', 'Retours & avoirs fournisseurs'],
                ] as [$r, $l])
                    <a href="{{ route($r) }}" class="flex items-center justify-between rounded-xl px-3 py-2 ring-1 ring-gp-border hover:bg-slate-50 dark:ring-white/10 dark:hover:bg-white/5">{{ $l }} <span class="text-gp-muted">→</span></a>
                @endforeach
            </div>
        </aside>
    </section>
@endsection
