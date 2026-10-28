@extends('layouts.app')

@section('title', 'Trésorerie')
@section('breadcrumb', 'Comptabilité')
@section('heading', 'Trésorerie (caisse & banque)')
@section('subtitle', 'Encaissements du point de vente, mouvements de caisse et écritures comptables manuelles.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        <form method="post" action="{{ route('accounting.cash.rebuild') }}">@csrf<button class="gp-btn-secondary">Recalculer soldes</button></form>
        <a href="{{ route('accounting.cash.create') }}" class="gp-btn-primary">Nouvelle écriture</a>
    </div>
@endsection

@section('content')
    @include('accounting._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-bold">Point de vente — du {{ $pos['from']->format('d/m/Y') }} au {{ $pos['to']->format('d/m/Y') }}</h2>
            @if(Route::has('pos.journal'))<a href="{{ route('pos.journal') }}" class="text-sm font-semibold text-gp-primary hover:underline">Journal de caisse détaillé →</a>@endif
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($pos['inflows'] as $label => $amount)
                <div class="rounded-xl bg-gp-surface/60 p-3 ring-1 ring-gp-border dark:ring-white/10"><p class="text-xs font-semibold uppercase text-gp-muted">{{ $label }}</p><p class="mt-1 text-lg font-bold text-emerald-700">{{ number_format($amount, 2, ',', ' ') }}</p></div>
            @endforeach
            <div class="rounded-xl p-3 ring-1 ring-gp-border dark:ring-white/10"><p class="text-xs font-semibold uppercase text-gp-muted">Entrées / sorties de caisse</p><p class="mt-1 text-lg font-bold">+{{ number_format($pos['cash_in'], 2, ',', ' ') }} / <span class="text-rose-700">−{{ number_format($pos['cash_out'], 2, ',', ' ') }}</span></p></div>
            <div class="rounded-xl p-3 ring-1 ring-gp-border dark:ring-white/10"><p class="text-xs font-semibold uppercase text-gp-muted">Total encaissé POS (net)</p><p class="mt-1 text-lg font-bold">{{ number_format($pos['total'], 2, ',', ' ') }} {{ $currency }}</p></div>
            <div class="rounded-xl p-3 ring-1 ring-gp-border dark:ring-white/10"><p class="text-xs font-semibold uppercase text-gp-muted">Chèques / effets / crédits à encaisser</p><p class="mt-1 text-lg font-bold text-amber-700">{{ number_format($pos['receivable'], 2, ',', ' ') }}</p></div>
        </div>
    </section>

    <h2 class="mb-2 text-sm font-bold">Écritures comptables de caisse / banque</h2>
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Entrées</p><p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($stats['in'], 2, ',', ' ') }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Sorties</p><p class="mt-1 text-xl font-bold text-rose-700">{{ number_format($stats['out'], 2, ',', ' ') }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Solde</p><p class="mt-1 text-xl font-bold">{{ number_format($stats['balance'], 2, ',', ' ') }} {{ $currency }}</p></div>
    </div>

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Libellé…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <select name="direction" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Sens</option>
                @foreach($directions as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['direction'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="source_type" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Source</option>
                @foreach($sourceTypes as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['source_type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="store_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Boutique</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($entries->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucune écriture</p>
                <a href="{{ route('accounting.cash.create') }}" class="gp-btn-primary mt-5">Saisir une écriture</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Libellé</th>
                            <th class="px-4 py-3">Sens</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Montant</th>
                            <th class="px-4 py-3">Solde</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($entries as $entry)
                            <tr>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $entry->entry_date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $entry->label }}</td>
                                <td class="px-4 py-3">
                                    <span class="gp-badge {{ $entry->direction === 'in' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $entry->directionLabel() }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ $entry->sourceTypeLabel() }}</td>
                                <td class="px-4 py-3 font-semibold {{ $entry->direction === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $entry->direction === 'in' ? '+' : '−' }}{{ number_format($entry->amount, 2, ',', ' ') }}
                                </td>
                                <td class="px-4 py-3">{{ $entry->balance_after !== null ? number_format($entry->balance_after, 2, ',', ' ') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $entries->links() }}</div>
        @endif
    </section>
@endsection
