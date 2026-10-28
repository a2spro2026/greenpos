@extends('layouts.app')
@section('title','Incidents')
@section('heading','Incidents')
@section('actions')<a href="{{ route('crm.incidents.create') }}" class="gp-btn-primary">Nouvel incident</a>@endsection
@section('content')
<div class="mb-4 grid gap-3 sm:grid-cols-3">
<div class="gp-card"><p class="text-xs text-gp-muted">Ouverts</p><p class="text-2xl font-bold">{{ $stats['open'] }}</p></div>
<div class="gp-card"><p class="text-xs text-gp-muted">Urgents</p><p class="text-2xl font-bold">{{ $stats['urgent'] }}</p></div>
<div class="gp-card"><p class="text-xs text-gp-muted">Résolus</p><p class="text-2xl font-bold">{{ $stats['resolved'] }}</p></div>
</div>
<section class="gp-card overflow-hidden p-0"><table class="min-w-full text-sm">
<thead class="bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">Titre</th><th class="px-4 py-3">Priorité</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Assigné</th><th></th></tr></thead>
<tbody>
@forelse($items as $i)
<tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $i->title }}</td>
<td class="px-4 py-3">{{ $priorities[$i->priority] ?? $i->priority }}</td>
<td class="px-4 py-3">{{ $statuses[$i->status] ?? $i->status }}</td>
<td class="px-4 py-3">{{ $i->assignee?->name ?: '—' }}</td>
<td class="px-4 py-3">
<form method="post" action="{{ route('crm.incidents.status', $i) }}" class="flex gap-1">@csrf
<select name="status" class="rounded-lg border px-2 py-1 text-xs">@foreach($statuses as $k=>$l)<option value="{{ $k }}" @selected($i->status===$k)>{{ $l }}</option>@endforeach</select>
<button class="text-xs font-semibold text-emerald-700">OK</button></form>
</td></tr>
@empty<tr><td colspan="5" class="px-4 py-10 text-center text-gp-muted">Aucun incident</td></tr>@endforelse
</tbody></table></section>{{ $items->links() }}
@endsection
