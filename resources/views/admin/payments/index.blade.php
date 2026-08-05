@extends('layouts.admin')
@section('title', 'Paiements')
@section('breadcrumb', 'Commercial')
@section('heading', 'Paiements')
@section('content')
<div class="pa-card overflow-x-auto !p-0">
<table class="pa-table">
<thead><tr><th>N°</th><th>Client</th><th>Montant</th><th>Provider</th><th>Statut</th><th>Date</th></tr></thead>
<tbody>
@forelse($payments as $p)
<tr>
<td class="pa-mono">{{ $p->number ?? $p->id }}</td>
<td>{{ $p->tenant?->name ?? '—' }}</td>
<td class="pa-mono">{{ number_format((float)$p->amount,2,',',' ') }} {{ $p->currency ?? '' }}</td>
<td>{{ $p->provider }}</td>
<td><span class="pa-badge {{ $p->status === 'paid' ? 'pa-badge-ok' : 'pa-badge-muted' }}">{{ $p->status }}</span></td>
<td>{{ $p->paid_at?->format('d/m/Y') ?? $p->created_at?->format('d/m/Y') }}</td>
</tr>
@empty
<tr><td colspan="6" class="text-zinc-500">Aucun paiement.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($payments->hasPages())<div class="mt-4">{{ $payments->links() }}</div>@endif
@endsection
