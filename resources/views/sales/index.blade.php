@extends('layouts.app')

@section('title', 'Liste des ventes')
@section('breadcrumb', 'Ventes / Liste')
@section('heading', 'Ventes')
@section('subtitle', 'Toutes les ventes — POS, devis convertis et manuelles.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('sales.export')
            <a href="{{ route('sales.export', request()->only(['status','origin','store_id','from','to'])) }}" class="gp-btn-secondary">Exporter CSV</a>
        @endcan
        @can('sales.create')
            <a href="{{ route('sales.create') }}" class="gp-btn-primary">Nouvelle vente</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('sales._nav')

    {{-- Filters --}}
    <form method="GET" action="{{ route('sales.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="gp-input w-full">
        </div>
        <select name="status" class="gp-select w-40">
            <option value="">Statut</option>
            @foreach($statuses as $k => $v)<option value="{{ $k }}" {{ ($filters['status'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
        </select>
        <select name="origin" class="gp-select w-36">
            <option value="">Origine</option>
            @foreach($origins as $k => $v)<option value="{{ $k }}" {{ ($filters['origin'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
        </select>
        <select name="store_id" class="gp-select w-40">
            <option value="">Boutique</option>
            @foreach($stores as $st)<option value="{{ $st->id }}" {{ ($filters['store_id'] ?? '') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="gp-input w-36" placeholder="Du">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="gp-input w-36" placeholder="Au">
        <button class="gp-btn-primary">Filtrer</button>
        @if(array_filter($filters ?? []))
            <a href="{{ route('sales.index') }}" class="text-sm text-gp-muted hover:text-gp-text">Effacer</a>
        @endif
    </form>

    {{-- Table --}}
    <section class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        @php
                            $sortable = fn($col, $label) => '<a href="'.route('sales.index', array_merge($filters ?? [], ['sort' => $col, 'dir' => ($filters['sort'] ?? '') === $col && ($filters['dir'] ?? 'desc') === 'asc' ? 'desc' : 'asc'])).'" class="hover:text-gp-text">'.$label.((($filters['sort'] ?? '') === $col) ? (($filters['dir'] ?? 'desc') === 'asc' ? ' ↑' : ' ↓') : '').'</a>';
                        @endphp
                        <th class="px-4 py-3">{!! $sortable('number', 'Réf') !!}</th>
                        <th class="px-4 py-3">{!! $sortable('sold_at', 'Date') !!}</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Origine</th>
                        <th class="px-4 py-3">Boutique</th>
                        <th class="px-4 py-3">Vendeur</th>
                        <th class="px-4 py-3 text-right">HT</th>
                        <th class="px-4 py-3 text-right">TVA</th>
                        <th class="px-4 py-3 text-right">{!! $sortable('total_ttc', 'TTC') !!}</th>
                        <th class="px-4 py-3">{!! $sortable('status', 'Statut') !!}</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('sales.show', $sale) }}" class="text-gp-primary hover:underline">{{ $sale->number }}</a></td>
                            <td class="px-4 py-3 text-gp-muted">{{ optional($sale->sold_at)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $sale->customer?->name ?? 'Passage' }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold dark:bg-white/10">{{ $sale->originLabel() }}</span></td>
                            <td class="px-4 py-3 text-gp-muted">{{ $sale->store?->name }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $sale->salesperson?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($sale->subtotal_ht, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($sale->tax_total, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums">{{ number_format($sale->total_ttc, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $sale->statusColor() }}">{{ $sale->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <a href="{{ route('sales.show', $sale) }}" class="rounded p-1 hover:bg-slate-100 dark:hover:bg-white/10" title="Voir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @can('sales.print')
                                        <a href="{{ route('sales.print', $sale) }}" class="rounded p-1 hover:bg-slate-100 dark:hover:bg-white/10" title="Imprimer" target="_blank">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-6 py-12 text-center text-gp-muted">Aucune vente trouvée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $sales->links() }}</div>
        @endif
    </section>
@endsection
