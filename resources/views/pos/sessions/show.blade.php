@extends('layouts.app')

@section('title', $session->number)
@section('breadcrumb', 'Ventes / POS / Sessions')
@section('heading', $session->number)
@section('subtitle', $session->statusLabel().' · '.$session->store?->name)

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('pos.sessions.index') }}" class="gp-btn-secondary">Retour</a>
        @if($session->isOpen())
            @can('pos.close')
                <a href="{{ route('pos.sessions.close.form', $session) }}" class="gp-btn-primary">Clôturer</a>
            @endcan
        @endif
    </div>
@endsection

@section('content')
    @include('pos._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Fond</p><p class="mt-2 text-2xl font-bold">{{ number_format($session->opening_float, 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Ventes</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($session->total_sales, 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Tickets</p><p class="mt-2 text-2xl font-bold">{{ $session->tickets_count }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Écart</p><p class="mt-2 text-2xl font-bold {{ abs((float) ($session->cash_difference ?? 0)) > 0.01 ? 'text-amber-600' : '' }}">{{ $session->status === 'closed' ? number_format($session->cash_difference, 2, ',', ' ') : '—' }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 lg:grid-cols-2">
        <article class="gp-card text-sm">
            <h2 class="mb-3 text-sm font-bold">Ouverture</h2>
            <p>{{ optional($session->opened_at)->format('d/m/Y H:i') }} · {{ $session->opener?->name }}</p>
            @if($session->opening_notes)<p class="mt-2 text-gp-muted">{{ $session->opening_notes }}</p>@endif
            @if($session->status === 'closed')
                <h2 class="mb-3 mt-6 text-sm font-bold">Clôture</h2>
                <p>{{ optional($session->closed_at)->format('d/m/Y H:i') }} · {{ $session->closer?->name }}</p>
                <dl class="mt-3 space-y-1">
                    <div class="flex justify-between"><dt class="text-gp-muted">Compté</dt><dd>{{ number_format($session->closing_counted, 2, ',', ' ') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Attendu</dt><dd>{{ number_format($session->expected_cash, 2, ',', ' ') }}</dd></div>
                </dl>
                @if($session->closing_notes)<p class="mt-2 text-gp-muted">{{ $session->closing_notes }}</p>@endif
            @endif
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Tickets de la session</h2></div>
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @forelse($session->sales as $sale)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <a href="{{ route('pos.tickets.show', $sale) }}" class="font-semibold text-gp-primary hover:underline">{{ $sale->number }}</a>
                        <span>{{ number_format($sale->total_ttc, 2, ',', ' ') }} · {{ $sale->statusLabel() }}</span>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-gp-muted">Aucun ticket.</li>
                @endforelse
            </ul>
        </article>
    </section>
@endsection
