@extends('layouts.admin')
@section('title', 'Abonnements')
@section('breadcrumb', 'Commercial')
@section('heading', 'Abonnements')
@section('content')
<form method="GET" class="mb-4 flex gap-2">
<select name="status" class="pa-select max-w-[12rem]" onchange="this.form.submit()">
<option value="">Tous</option>
@foreach(['trialing','active','suspended','cancelled','past_due','expired'] as $st)
<option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ $st }}</option>
@endforeach
</select>
</form>
<div class="pa-card overflow-x-auto !p-0">
<table class="pa-table">
<thead><tr><th>Client</th><th>Plan</th><th>Statut</th><th>Montant</th><th>Échéance</th></tr></thead>
<tbody>
@forelse($subscriptions as $sub)
<tr>
<td>{{ $sub->tenant?->name ?? '—' }}</td>
<td>{{ $sub->plan?->name ?? '—' }}</td>
<td><span class="pa-badge {{ $sub->status === 'active' ? 'pa-badge-ok' : ($sub->status === 'trialing' ? 'pa-badge-warn' : 'pa-badge-muted') }}">{{ $sub->status }}</span></td>
<td class="pa-mono">{{ number_format((float)$sub->amount,2,',',' ') }}</td>
<td>{{ $sub->ends_at?->format('d/m/Y') ?? '—' }}</td>
</tr>
@empty
<tr><td colspan="5" class="text-zinc-500">Aucun abonnement.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($subscriptions->hasPages())<div class="mt-4">{{ $subscriptions->links() }}</div>@endif
@endsection
