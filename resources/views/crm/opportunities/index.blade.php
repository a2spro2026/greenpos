@extends('layouts.app')
@section('title', 'Opportunités')
@section('breadcrumb', 'CRM / Opportunités')
@section('heading', 'Opportunités')
@section('actions')
    <a href="{{ route('crm.pipeline') }}" class="gp-btn-secondary">Pipeline</a>
    <a href="{{ route('crm.opportunities.create') }}" class="gp-btn-primary">Nouvelle</a>
@endsection
@section('content')
@include('crm._nav')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="gp-input max-w-xs" placeholder="Rechercher…">
    <select name="stage" class="gp-input max-w-[12rem]"><option value="">Étape</option>@foreach($stages as $k=>$v)<option value="{{ $k }}" {{ ($filters['stage']??'')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select>
    <button class="gp-btn-secondary">Filtrer</button>
</form>
<div class="gp-card overflow-hidden p-0">
<table class="gp-table">
<thead><tr><th>Opportunité</th><th>Étape</th><th>Montant</th><th>Proba.</th><th>Owner</th><th>Close</th></tr></thead>
<tbody>
@forelse($opportunities as $o)
<tr>
    <td><a href="{{ route('crm.opportunities.show', $o) }}" class="font-semibold text-gp-primary hover:underline">{{ $o->name }}</a><p class="text-xs text-gp-muted">{{ $o->number }}</p></td>
    <td>{{ $o->stageLabel() }}</td>
    <td>{{ number_format($o->amount, 0, ',', ' ') }}</td>
    <td>{{ $o->probability }}%</td>
    <td>{{ $o->owner?->name ?: '—' }}</td>
    <td class="text-gp-muted">{{ optional($o->expected_close_on)->format('d/m/Y') }}</td>
</tr>
@empty
<tr><td colspan="6" class="py-16 text-center text-gp-muted">Aucune opportunité</td></tr>
@endforelse
</tbody>
</table>
@if($opportunities->hasPages())<div class="border-t border-gp-border px-4 py-3">{{ $opportunities->links() }}</div>@endif
</div>
@endsection
