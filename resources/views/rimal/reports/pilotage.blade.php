@extends('layouts.app')

@section('title', 'Pilotage')
@section('breadcrumb', 'Rapports / Pilotage')
@section('heading', 'Tableau de pilotage')
@section('subtitle', 'Comparaison avec la période précédente, marge, trésorerie — par boutique et consolidé.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('reports.export')<a href="{{ route('reports.pilotage.export', $query) }}" class="gp-btn-secondary">Export Excel</a>@endcan
        @can('reports.print')<a href="{{ route('reports.extra.print', ['type' => 'pilotage'] + $query) }}" target="_blank" class="gp-btn-secondary">PDF / Imprimer</a>@endcan
    </div>
@endsection

@php
    $fmt = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $cards = [
        'revenue' => ["Chiffre d'affaires TTC", true], 'margin' => ['Marge brute estimée', true], 'margin_rate' => ['Taux de marge', false],
        'count' => ['Nombre de ventes', false], 'avg_ticket' => ['Panier moyen', true], 'purchases' => ['Achats fournisseurs', true],
        'expenses' => ['Dépenses', true], 'collected' => ['Encaissements', true], 'treasury' => ['Trésorerie nette', true], 'receivable' => ['Encours à encaisser', true],
    ];
@endphp

@section('content')
    @include('reports._nav')
    @include('rimal.reports._filters')

    <p class="mb-4 text-xs text-gp-muted">Période : {{ $filters['from']->format('d/m/Y') }} → {{ $filters['to']->format('d/m/Y') }} · comparée à {{ $pFrom->format('d/m/Y') }} → {{ $pTo->format('d/m/Y') }}</p>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($cards as $k => [$label, $money])
            @php $v = $variation[$k] ?? null; $good = in_array($k, ['expenses', 'purchases', 'receivable']) ? ($v !== null && $v < 0) : ($v !== null && $v > 0); @endphp
            <article class="gp-kpi">
                <p class="text-xs font-semibold uppercase text-gp-muted">{{ $label }}</p>
                <p class="mt-2 text-xl font-bold">{{ $k === 'count' ? $current[$k] : $fmt($current[$k]) }}{{ $k === 'margin_rate' ? ' %' : ($money ? ' '.$currency : '') }}</p>
                <p class="mt-1 text-xs text-gp-muted">Préc. : {{ $k === 'count' ? $previous[$k] : $fmt($previous[$k]) }}
                    @if($v !== null)<span class="ml-1 font-semibold {{ $good ? 'text-emerald-600' : 'text-rose-600' }}">{{ $v > 0 ? '+' : '' }}{{ $v }} %</span>@endif
                </p>
            </article>
        @endforeach
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-3 text-sm font-bold">Trésorerie de la période</h2>
            <table class="w-full text-sm">
                @foreach($current['inflows'] as $label => $amount)
                    <tr class="border-b border-gp-border/50"><td class="py-1">{{ $label }}</td><td class="py-1 text-right">{{ $fmt($amount) }}</td></tr>
                @endforeach
                <tr class="border-b border-gp-border/50"><td class="py-1">Entrées de caisse</td><td class="py-1 text-right">{{ $fmt($current['cash_in']) }}</td></tr>
                <tr class="border-b border-gp-border/50"><td class="py-1">Sorties de caisse</td><td class="py-1 text-right text-rose-600">−{{ $fmt($current['cash_out']) }}</td></tr>
                <tr class="border-b border-gp-border/50"><td class="py-1">Dépenses</td><td class="py-1 text-right text-rose-600">−{{ $fmt($current['expenses']) }}</td></tr>
                <tr class="font-bold"><td class="py-2">Trésorerie nette</td><td class="py-2 text-right">{{ $fmt($current['treasury']) }} {{ $currency }}</td></tr>
                <tr><td class="py-1 text-gp-muted">Encours chèques / effets / crédits</td><td class="py-1 text-right text-gp-muted">{{ $fmt($current['receivable']) }}</td></tr>
            </table>
        </article>
        <article class="gp-card overflow-x-auto">
            <h2 class="mb-3 text-sm font-bold">Par boutique</h2>
            @if($byStore->isEmpty())
                <p class="text-sm text-gp-muted">Filtre boutique actif — vue consolidée désactivée.</p>
            @else
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs uppercase text-gp-muted"><th class="py-1">Boutique</th><th class="text-right">CA TTC</th><th class="text-right">Marge</th><th class="text-right">Ventes</th><th class="text-right">Trésorerie</th></tr></thead>
                    <tbody>
                        @foreach($byStore as $s)
                            <tr class="border-b border-gp-border/50"><td class="py-1">{{ $s['store'] }}</td><td class="text-right">{{ $fmt($s['revenue']) }}</td><td class="text-right">{{ $fmt($s['margin']) }}</td><td class="text-right">{{ $s['count'] }}</td><td class="text-right">{{ $fmt($s['treasury']) }}</td></tr>
                        @endforeach
                        <tr class="font-bold"><td class="py-2">Consolidé</td><td class="text-right">{{ $fmt($current['revenue']) }}</td><td class="text-right">{{ $fmt($current['margin']) }}</td><td class="text-right">{{ $current['count'] }}</td><td class="text-right">{{ $fmt($current['treasury']) }}</td></tr>
                    </tbody>
                </table>
            @endif
        </article>
    </section>
    <p class="text-xs text-gp-muted">Marge estimée = CA HT − quantités vendues × prix d'achat produit. Trésorerie = encaissements effectifs + entrées − sorties de caisse − dépenses (hors paiements différés non encaissés).</p>
@endsection
