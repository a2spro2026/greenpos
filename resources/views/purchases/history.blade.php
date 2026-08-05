@extends('layouts.app')

@section('title', 'Historique achats')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Historique des achats')
@section('subtitle', 'Commandes, réceptions et modifications.')

@section('content')
    @include('purchases._nav')

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-5">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Message, action, N° commande…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="action" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Toutes actions</option>
                @foreach(['created','updated','sent','confirmed','cancelled','receipt_created','receipt_validated','from_request'] as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-3">
        <article class="gp-card xl:col-span-2 overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Journal des modifications</h2></div>
            @if($logs->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucun événement pour ces filtres.</div>
            @else
                <ol class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($logs as $log)
                        <li class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold">{{ $log->message }}</p>
                                <span class="text-xs text-gp-muted">{{ $log->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gp-muted">
                                {{ $log->action }}
                                @if($log->order) · <a href="{{ route('purchases.orders.show', $log->order) }}" class="text-gp-primary hover:underline">{{ $log->order->number }}</a>@endif
                                · {{ $log->user?->name ?: 'Système' }}
                            </p>
                        </li>
                    @endforeach
                </ol>
                <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $logs->links() }}</div>
            @endif
        </article>

        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Dernières réceptions</h2></div>
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @forelse($receipts as $receipt)
                    <li class="px-5 py-3 text-sm">
                        <a href="{{ route('purchases.receipts.show', $receipt) }}" class="font-semibold hover:text-gp-primary">{{ $receipt->number }}</a>
                        <p class="text-xs text-gp-muted">{{ $receipt->order?->supplier?->name }} · {{ $receipt->statusLabel() }}</p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gp-muted">Aucune réception.</li>
                @endforelse
            </ul>
        </article>
    </section>
@endsection
