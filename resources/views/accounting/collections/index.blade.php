@extends('layouts.app')
@section('title','Encaissements')
@section('heading','Suivi encaissements')
@section('content')
@include('accounting._nav')
@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
<div class="mb-4 flex gap-2"><a href="?filter=" class="gp-btn-secondary">Tous</a><a href="?filter=today" class="gp-btn-secondary">Aujourd'hui</a><a href="?filter=overdue" class="gp-btn-secondary">En retard</a></div>
<section class="gp-card overflow-hidden p-0"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">Vente</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Montant</th><th class="px-4 py-3">Échéance</th><th class="px-4 py-3">Statut</th><th></th></tr></thead>
<tbody>
@forelse($items as $p)
<tr class="border-t"><td class="px-4 py-3">{{ $p->sale?->number }}</td><td class="px-4 py-3">{{ $p->sale?->customer?->name ?: '—' }}</td>
<td class="px-4 py-3 font-mono">{{ number_format($p->amount,2,',',' ') }} {{ $currency }}</td>
<td class="px-4 py-3">{{ optional($p->due_at)->format('d/m/Y') ?: '—' }}</td>
<td class="px-4 py-3">{{ $p->status }}</td>
<td class="px-4 py-3 text-right space-x-2">
@if($p->status==='pending')
<form class="inline" method="post" action="{{ route('accounting.collections.collect', $p) }}">@csrf<button class="font-semibold text-emerald-700">Encaisser</button></form>
<form class="inline" method="post" action="{{ route('accounting.collections.cancel', $p) }}">@csrf<button class="text-rose-700">Annuler</button></form>
@endif
</td></tr>
@empty<tr><td colspan="6" class="px-4 py-10 text-center text-gp-muted">Aucun paiement différé</td></tr>@endforelse
</tbody></table></section>{{ $items->links() }}
@endsection
