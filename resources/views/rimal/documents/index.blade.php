@extends('layouts.app')

@section('title', 'Documents commerciaux')
@section('breadcrumb', 'Rapports / Documents')
@section('heading', 'Documents commerciaux')
@section('subtitle', 'Vue centrale : devis, commandes, bons de livraison, factures, bons de commande fournisseur et tickets.')

@php
    $types = ['' => 'Tous', 'quote' => 'Devis', 'order' => 'Commandes / ventes', 'delivery' => 'Bons de livraison', 'invoice' => 'Factures / avoirs', 'purchase' => 'Bons de commande fournisseur', 'ticket' => 'Tickets de caisse'];
@endphp

@section('content')
    @include('reports._nav')
    <form method="get" class="gp-card mb-4 grid gap-3 sm:grid-cols-5">
        <label class="block text-xs"><span class="gp-label">Du</span><input type="date" name="from" value="{{ $filters['from']->toDateString() }}" class="gp-input"></label>
        <label class="block text-xs"><span class="gp-label">Au</span><input type="date" name="to" value="{{ $filters['to']->toDateString() }}" class="gp-input"></label>
        <label class="block text-xs"><span class="gp-label">Type</span><select name="type" class="gp-select w-full">@foreach($types as $k => $l)<option value="{{ $k }}" @selected(($type ?? '') === $k)>{{ $l }}</option>@endforeach</select></label>
        <label class="block text-xs"><span class="gp-label">N° document</span><input type="search" name="q" value="{{ $search }}" class="gp-input"></label>
        <div class="flex items-end"><button class="gp-btn-primary w-full">Filtrer</button></div>
    </form>

    <article class="gp-card overflow-x-auto">
        <p class="mb-2 text-xs text-gp-muted">{{ count($docs) }} document(s)</p>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs uppercase text-gp-muted"><th class="px-2 py-2">Date</th><th>Type</th><th>N°</th><th>Tiers</th><th>Statut</th><th class="text-right">Total</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($docs as $d)
                    <tr class="border-b border-gp-border/50">
                        <td class="px-2 py-1">{{ optional($d['date'])->format('d/m/Y') }}</td>
                        <td>{{ $d['type'] }}</td>
                        <td class="font-semibold">{{ $d['number'] }}</td>
                        <td>{{ $d['party'] ?? '—' }}</td>
                        <td>{{ $d['status'] }}</td>
                        <td class="text-right">{{ number_format($d['total'], 2, ',', ' ') }} {{ $currency }}</td>
                        <td class="whitespace-nowrap text-right">
                            @if($d['url'])<a href="{{ $d['url'] }}" class="text-gp-primary hover:underline">Voir</a>@endif
                            @if($d['print'])<a href="{{ $d['print'] }}" target="_blank" class="ml-2 text-gp-primary hover:underline">PDF</a>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gp-muted">Aucun document sur cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </article>
@endsection
