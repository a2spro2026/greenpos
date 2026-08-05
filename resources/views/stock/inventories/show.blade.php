@extends('layouts.app')

@section('title', $inventory->name)
@section('breadcrumb', 'Catalogue / Stock / Inventaires')
@section('heading', $inventory->name)
@section('subtitle', $inventory->store?->name.' · '.$inventory->statusLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('stock.inventories.index') }}" class="gp-btn-secondary">Retour</a>
        @if(in_array($inventory->status, ['draft', 'in_progress'], true))
            <form method="post" action="{{ route('stock.inventories.validate', $inventory) }}" onsubmit="return confirm('Valider cet inventaire et ajuster les écarts ?')">
                @csrf
                <button class="gp-btn-primary">Valider</button>
            </form>
            <form method="post" action="{{ route('stock.inventories.cancel', $inventory) }}" onsubmit="return confirm('Annuler cet inventaire ?')">
                @csrf
                <button class="gp-btn-secondary text-rose-600">Annuler</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    @include('stock._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-500/20 dark:bg-rose-500/10">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        $counted = $inventory->lines->where('is_counted', true)->count();
        $total = $inventory->lines->count();
        $variances = $inventory->lines->filter(fn ($l) => $l->is_counted && (float) $l->difference !== 0.0)->count();
    @endphp

    <section class="mb-4 grid gap-4 sm:grid-cols-3">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Lignes</p><p class="mt-2 text-3xl font-bold">{{ $total }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Comptées</p><p class="mt-2 text-3xl font-bold text-gp-primary">{{ $counted }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Écarts</p><p class="mt-2 text-3xl font-bold text-gp-warning">{{ $variances }}</p></article>
    </section>

    @if(in_array($inventory->status, ['draft', 'in_progress'], true))
        <section class="gp-card mb-4">
            <h2 class="mb-3 text-sm font-bold">Scanner un code</h2>
            <form method="post" action="{{ route('stock.inventories.scan', $inventory) }}" class="grid gap-3 sm:grid-cols-4">
                @csrf
                <input type="text" name="code" autofocus placeholder="Code-barres ou SKU" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm sm:col-span-2 dark:border-white/10 dark:bg-[#0f1614]" required>
                <input type="number" step="0.001" name="counted_qty" placeholder="Qté (optionnel, +1)" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <button class="gp-btn-primary">Scanner</button>
            </form>
        </section>
    @endif

    <section class="gp-card overflow-hidden p-0">
        @if($inventory->lines->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-lg font-bold">Aucune ligne</p>
                <p class="mt-2 text-sm text-gp-muted">Initialisez d’abord des stocks pour cette boutique.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Produit</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Attendu</th>
                            <th class="px-4 py-3">Compté</th>
                            <th class="px-4 py-3">Écart</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($inventory->lines as $line)
                            <tr class="{{ $line->is_counted && (float) $line->difference !== 0.0 ? 'bg-amber-50/60 dark:bg-amber-500/5' : '' }}">
                                <td class="px-4 py-3 font-semibold">{{ $line->product?->name }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $line->product?->sku }}</td>
                                <td class="px-4 py-3">{{ number_format($line->expected_qty, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3">{{ $line->is_counted ? number_format($line->counted_qty, 3, ',', ' ') : '—' }}</td>
                                <td class="px-4 py-3 font-semibold {{ (float) $line->difference < 0 ? 'text-rose-600' : ((float) $line->difference > 0 ? 'text-emerald-600' : 'text-gp-muted') }}">
                                    {{ $line->is_counted ? number_format($line->difference, 3, ',', ' ') : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if(in_array($inventory->status, ['draft', 'in_progress'], true))
                                        <form method="post" action="{{ route('stock.inventories.count', $inventory) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="line_id" value="{{ $line->id }}">
                                            <input type="number" step="0.001" name="counted_qty" value="{{ $line->counted_qty ?? $line->expected_qty }}" class="w-24 rounded-lg border border-gp-border px-2 py-1.5 text-sm dark:border-white/10 dark:bg-[#0f1614]" required>
                                            <button class="text-xs font-semibold text-gp-primary hover:underline">OK</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gp-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
