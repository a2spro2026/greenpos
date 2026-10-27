@extends('layouts.app')

@section('title', $loss->number)
@section('breadcrumb', 'Opérations / Pertes')
@section('heading', $loss->number)
@section('subtitle', $loss->typeLabel().' — '.$loss->loss_date?->format('d/m/Y'))

@section('actions')
    <a href="{{ route('ops.losses.index') }}" class="gp-btn-secondary">Liste</a>
@endsection

@section('content')
    @include('production._nav')

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Type</p><p class="mt-1"><span class="gp-badge {{ $loss->typeColor() }}">{{ $loss->typeLabel() }}</span></p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Boutique</p><p class="mt-1 font-semibold">{{ $loss->store?->name }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Coût total</p><p class="mt-1 text-xl font-bold">{{ number_format($loss->total_cost, 2, ',', ' ') }}</p></div>
    </div>

    <section class="gp-card mb-4">
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Responsable</dt><dd class="mt-1">{{ $loss->responsible_name ?: '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Créé par</dt><dd class="mt-1">{{ $loss->creator?->name ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gp-muted">Notes</dt><dd class="mt-1">{{ $loss->notes ?: '—' }}</dd></div>
        </dl>
    </section>

    <section class="gp-card overflow-hidden p-0">
        <div class="border-b border-gp-border px-4 py-3 text-sm font-bold dark:border-white/10">Lignes</div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-gp-muted dark:bg-white/5">
                <tr><th class="px-4 py-3 text-left">Produit</th><th class="px-4 py-3 text-left">Qté</th><th class="px-4 py-3 text-left">Coût u.</th><th class="px-4 py-3 text-left">Total</th></tr>
            </thead>
            <tbody class="divide-y divide-gp-border dark:divide-white/10">
                @foreach($loss->lines as $line)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $line->product?->name }}</td>
                        <td class="px-4 py-3">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                        <td class="px-4 py-3">{{ number_format($line->unit_cost, 2, ',', ' ') }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($line->total_cost, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
