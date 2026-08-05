@extends('layouts.admin')
@section('title', 'Boutiques')
@section('breadcrumb', 'Pilotage')
@section('heading', 'Boutiques')
@section('content')
<form method="GET" class="mb-4"><input type="search" name="q" value="{{ request('q') }}" class="pa-input max-w-sm" placeholder="Rechercher boutique ou entreprise…"></form>
<div class="pa-card overflow-x-auto !p-0">
<table class="pa-table">
<thead><tr><th>Boutique</th><th>Entreprise</th><th>Ville</th><th>Statut</th></tr></thead>
<tbody>
@forelse($stores as $store)
<tr>
<td class="font-semibold">{{ $store->name }} @if($store->is_default)<span class="pa-badge pa-badge-muted">Principale</span>@endif</td>
<td>{{ $store->company?->name ?? '—' }}</td>
<td>{{ $store->city ?: '—' }}</td>
<td><span class="pa-badge {{ $store->is_active ? 'pa-badge-ok' : 'pa-badge-danger' }}">{{ $store->is_active ? 'Active' : 'Inactive' }}</span></td>
</tr>
@empty
<tr><td colspan="4" class="text-zinc-500">Aucune boutique.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($stores->hasPages())<div class="mt-4">{{ $stores->links() }}</div>@endif
@endsection
