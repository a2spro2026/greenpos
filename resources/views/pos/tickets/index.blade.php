@extends('layouts.app')

@section('title', 'Tickets POS')
@section('breadcrumb', 'Ventes / POS / Tickets')
@section('heading', 'Tickets')
@section('subtitle', 'Historique des ventes caisse, réimpression et annulation.')

@section('actions')
    <a href="{{ route('pos.terminal') }}" class="gp-btn-primary">Ouvrir le terminal</a>
@endsection

@section('content')
    @include('pos._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-100">{{ session('success') }}</div>
    @endif

    <form method="get" class="mb-4 grid gap-3 rounded-2xl bg-white p-4 ring-1 ring-gp-border dark:bg-white/5 dark:ring-white/10 sm:grid-cols-4">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N° ticket ou client…" class="gp-input sm:col-span-2">
        <select name="status" class="gp-input">
            <option value="">Tous les statuts</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="gp-input flex-1">
            <button type="submit" class="gp-btn-secondary shrink-0">Filtrer</button>
        </div>
    </form>

    <div class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="gp-table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Caissier</th>
                        <th>Paiement</th>
                        <th class="text-right">Total</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $sale)
                        <tr>
                            <td class="font-semibold"><a href="{{ route('pos.tickets.show', $sale) }}" class="text-gp-primary hover:underline">{{ $sale->number }}</a></td>
                            <td class="text-sm text-gp-muted">{{ optional($sale->completed_at ?? $sale->held_at ?? $sale->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $sale->customer?->name ?? 'Passage' }}</td>
                            <td>{{ $sale->cashier?->name ?? '—' }}</td>
                            <td class="text-sm">{{ $sale->payments->map->methodLabel()->unique()->implode(', ') ?: '—' }}</td>
                            <td class="text-right font-bold">{{ number_format($sale->total_ttc, 2, ',', ' ') }}</td>
                            <td><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $sale->statusColor() }}">{{ $sale->statusLabel() }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('pos.tickets.show', $sale) }}" class="text-sm font-semibold text-gp-primary hover:underline">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-12 text-center text-sm text-gp-muted">Aucun ticket.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $tickets->links() }}</div>
        @endif
    </div>
@endsection
