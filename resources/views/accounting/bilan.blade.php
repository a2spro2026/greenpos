@extends('layouts.app')
@section('title','Bilan')
@section('heading','Bilan simplifié')
@section('content')
@include('accounting._nav')
<form class="mb-4 flex gap-2"><input type="date" name="as_of" value="{{ $summary['as_of'] }}" class="rounded-xl border px-3 py-2"><button class="gp-btn-secondary">Actualiser</button></form>
<section class="gp-card overflow-hidden p-0"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">Compte</th><th class="px-4 py-3">Type</th><th class="px-4 py-3 text-right">Solde</th></tr></thead>
<tbody>@foreach($summary['rows'] as $r)<tr class="border-t"><td class="px-4 py-3">{{ $r['code'] }} — {{ $r['name'] }}</td><td class="px-4 py-3">{{ $r['type'] }}</td><td class="px-4 py-3 text-right font-mono">{{ number_format($r['balance'], 2, ',', ' ') }}</td></tr>@endforeach</tbody></table></section>
<div class="mt-4 gp-card"><p class="text-sm text-gp-muted">Résultat</p><p class="text-2xl font-bold">{{ number_format($summary['result'], 2, ',', ' ') }}</p></div>
@endsection
