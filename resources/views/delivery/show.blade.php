@extends('layouts.app')

@section('title', $agent->name)
@section('breadcrumb', 'Livraison')
@section('heading', $agent->name)
@section('subtitle', $agent->typeLabel().($agent->platform_name ? ' — '.$agent->platform_name : ''))

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('delivery.edit', $agent) }}" class="gp-btn-secondary">Modifier</a>
        <form method="post" action="{{ route('delivery.destroy', $agent) }}" onsubmit="return confirm('Archiver ce livreur ?')">
            @csrf @method('DELETE')
            <button class="gp-btn-secondary text-rose-600">Archiver</button>
        </form>
    </div>
@endsection

@section('content')
    @include('delivery._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Livraisons</p><p class="mt-1 text-xl font-bold">{{ $stats['deliveries'] }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">CA livré</p><p class="mt-1 text-xl font-bold">{{ number_format($stats['revenue'], 2, ',', ' ') }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Commissions</p><p class="mt-1 text-xl font-bold">{{ number_format($stats['commission'], 2, ',', ' ') }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Ticket moyen</p><p class="mt-1 text-xl font-bold">{{ number_format($stats['avg_ticket'], 2, ',', ' ') }}</p></div>
    </div>

    <section class="gp-card mb-4">
        <form method="get" class="flex flex-wrap gap-3 items-end">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Du</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Au</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card">
        <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Téléphone</dt><dd class="mt-1">{{ $agent->phone ?: '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Email</dt><dd class="mt-1">{{ $agent->email ?: '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Boutique</dt><dd class="mt-1">{{ $agent->store?->name ?: '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Commission</dt><dd class="mt-1">{{ number_format($agent->commission_percent, 1) }}% + {{ number_format($agent->commission_fixed, 2, ',', ' ') }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gp-muted">Notes</dt><dd class="mt-1">{{ $agent->notes ?: '—' }}</dd></div>
        </dl>
    </section>
@endsection
