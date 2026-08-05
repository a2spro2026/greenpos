@extends('layouts.admin')
@section('title', 'Journal')
@section('breadcrumb', 'Plateforme')
@section('heading', 'Journal d\'activité')
@section('content')
<form method="GET" class="mb-4">
<select name="category" class="pa-select max-w-xs" onchange="this.form.submit()">
<option value="">Toutes catégories</option>
@foreach(['tenant','security','platform','billing','subscription'] as $cat)
<option value="{{ $cat }}" @selected(($filters['category'] ?? '') === $cat)>{{ $cat }}</option>
@endforeach
</select>
</form>
<div class="pa-card overflow-x-auto !p-0">
<table class="pa-table">
<thead><tr><th>Date</th><th>Catégorie</th><th>Sévérité</th><th>Événement</th><th>Acteur</th></tr></thead>
<tbody>
@forelse($events as $ev)
<tr>
<td class="whitespace-nowrap pa-mono text-xs">{{ $ev->occurred_at?->format('d/m/Y H:i') ?? $ev->created_at?->format('d/m/Y H:i') }}</td>
<td>{{ $ev->category }}</td>
<td><span class="pa-badge {{ $ev->severity === 'critical' ? 'pa-badge-danger' : ($ev->severity === 'warning' ? 'pa-badge-warn' : 'pa-badge-muted') }}">{{ $ev->severity }}</span></td>
<td>
<div class="font-semibold">{{ $ev->title }}</div>
<div class="text-xs text-zinc-500">{{ $ev->body }} @if($ev->tenant)· {{ $ev->tenant->name }}@endif</div>
</td>
<td>{{ $ev->user?->name ?? '—' }}</td>
</tr>
@empty
<tr><td colspan="5" class="text-zinc-500">Aucun événement.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($events->hasPages())<div class="mt-4">{{ $events->links() }}</div>@endif
@endsection
