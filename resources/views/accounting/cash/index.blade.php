@extends('layouts.app')

@section('title', 'Journal de caisse')
@section('breadcrumb', 'Comptabilité')
@section('heading', 'Journal de caisse')
@section('subtitle', 'Entrées et sorties — solde courant.')

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
