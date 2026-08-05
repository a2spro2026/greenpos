@extends('layouts.app')

@section('title', 'Liste des devis')
@section('breadcrumb', 'Ventes / Devis')
@section('heading', 'Liste des devis')
@section('subtitle', 'Recherche, filtres et export.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('quotes.export')
            <a href="{{ route('quotes.export', request()->query()) }}" class="gp-btn-secondary">Export Excel</a>
        @endcan
        @can('quotes.create')
            <a href="{{ route('quotes.create') }}" class="gp-btn-primary">Nouveau devis</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('quotes._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N°, client, référence…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="status" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous statuts</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
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
        @if($quotes->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-500/15">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                </div>
                <p class="text-lg font-bold">Aucun devis</p>
                <p class="mt-2 text-sm text-gp-muted">Créez votre première proposition commerciale.</p>
                @can('quotes.create')
                    <a href="{{ route('quotes.create') }}" class="gp-btn-primary mt-5">Créer un devis</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-3 py-3">Numéro</th>
                            <th class="px-3 py-3">Client</th>
                            <th class="px-3 py-3">Date</th>
                            <th class="px-3 py-3">Validité</th>
                            <th class="px-3 py-3 text-right">Total</th>
                            <th class="px-3 py-3">Statut</th>
                            <th class="px-3 py-3">Commercial</th>
                            <th class="px-3 py-3">Boutique</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($quotes as $quote)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-3 py-3 font-semibold whitespace-nowrap"><a href="{{ route('quotes.show', $quote) }}" class="text-gp-primary hover:underline">{{ $quote->number }}</a></td>
                                <td class="px-3 py-3">{{ $quote->customer?->displayName() }}</td>
                                <td class="px-3 py-3 text-gp-muted whitespace-nowrap">{{ optional($quote->quoted_at)->format('d/m/Y') }}</td>
                                <td class="px-3 py-3 text-gp-muted whitespace-nowrap">{{ optional($quote->valid_until)->format('d/m/Y') ?: '—' }}</td>
                                <td class="px-3 py-3 text-right font-bold">{{ number_format($quote->total_ttc, 2, ',', ' ') }}</td>
                                <td class="px-3 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $quote->statusColor() }}">{{ $quote->statusLabel() }}</span></td>
                                <td class="px-3 py-3 text-gp-muted">{{ $quote->salesperson?->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-gp-muted">{{ $quote->store?->name }}</td>
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('quotes.show', $quote) }}" class="text-xs font-semibold text-gp-primary hover:underline">Voir</a>
                                    @can('quotes.print')
                                        <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" class="ml-2 text-xs font-semibold text-gp-muted hover:text-gp-primary">PDF</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($quotes->hasPages())
                <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $quotes->links() }}</div>
            @endif
        @endif
    </section>
@endsection
