@extends('layouts.app')
@section('title', 'Activités CRM')
@section('breadcrumb', 'CRM / Activités')
@section('heading', 'Activités commerciales')
@section('actions')
    <a href="{{ route('crm.activities.create') }}" class="gp-btn-primary">Nouvelle activité</a>
@endsection
@section('content')
@include('crm._nav')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <select name="type" class="gp-input max-w-[10rem]"><option value="">Type</option>@foreach($types as $k=>$v)<option value="{{ $k }}" {{ ($filters['type']??'')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select>
    <select name="status" class="gp-input max-w-[10rem]"><option value="">Statut</option>@foreach($statuses as $k=>$v)<option value="{{ $k }}" {{ ($filters['status']??'')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select>
    <button class="gp-btn-secondary">Filtrer</button>
</form>
<div class="gp-card overflow-hidden p-0">
<table class="gp-table">
<thead><tr><th>Activité</th><th>Type</th><th>Lié à</th><th>Quand</th><th>Statut</th><th></th></tr></thead>
<tbody>
@forelse($activities as $a)
<tr>
    <td><a href="{{ route('crm.activities.show', $a) }}" class="font-semibold text-gp-primary">{{ $a->subject }}</a></td>
    <td>{{ $a->typeLabel() }}</td>
    <td class="text-sm text-gp-muted">{{ $a->lead?->displayName() ?: ($a->opportunity?->name ?: '—') }}</td>
    <td class="text-gp-muted">{{ optional($a->starts_at ?: $a->due_at)->format('d/m/Y H:i') ?: '—' }}</td>
    <td>{{ $a->statusLabel() }}</td>
    <td>@if($a->status==='planned')<form method="POST" action="{{ route('crm.activities.complete', $a) }}">@csrf<button class="text-xs font-semibold text-emerald-600">Terminer</button></form>@endif</td>
</tr>
@empty
<tr><td colspan="6" class="py-16 text-center text-gp-muted">Aucune activité</td></tr>
@endforelse
</tbody>
</table>
@if($activities->hasPages())<div class="border-t border-gp-border px-4 py-3">{{ $activities->links() }}</div>@endif
</div>
@endsection
