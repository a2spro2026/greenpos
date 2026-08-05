@extends('layouts.app')
@section('title', $activity->subject)
@section('breadcrumb', 'CRM / Activités')
@section('heading', $activity->subject)
@section('actions')
@if($activity->status==='planned')
<form method="POST" action="{{ route('crm.activities.complete', $activity) }}">@csrf<button class="gp-btn-primary">Marquer terminée</button></form>
@endif
@endsection
@section('content')
@include('crm._nav')
<article class="gp-card max-w-2xl space-y-3 text-sm">
    <p><span class="gp-badge bg-slate-100 text-slate-700">{{ $activity->typeLabel() }}</span> <span class="gp-badge bg-emerald-100 text-emerald-800">{{ $activity->statusLabel() }}</span></p>
    <dl class="grid gap-3 sm:grid-cols-2">
        <div><dt class="text-xs text-gp-muted">Début</dt><dd>{{ optional($activity->starts_at)->format('d/m/Y H:i') ?: '—' }}</dd></div>
        <div><dt class="text-xs text-gp-muted">Fin</dt><dd>{{ optional($activity->ends_at)->format('d/m/Y H:i') ?: '—' }}</dd></div>
        <div><dt class="text-xs text-gp-muted">Échéance</dt><dd>{{ optional($activity->due_at)->format('d/m/Y H:i') ?: '—' }}</dd></div>
        <div><dt class="text-xs text-gp-muted">Owner</dt><dd>{{ $activity->owner?->name ?: '—' }}</dd></div>
        <div><dt class="text-xs text-gp-muted">Lead</dt><dd>@if($activity->lead)<a class="text-gp-primary" href="{{ route('crm.leads.show', $activity->lead) }}">{{ $activity->lead->displayName() }}</a>@else — @endif</dd></div>
        <div><dt class="text-xs text-gp-muted">Opportunité</dt><dd>@if($activity->opportunity)<a class="text-gp-primary" href="{{ route('crm.opportunities.show', $activity->opportunity) }}">{{ $activity->opportunity->name }}</a>@else — @endif</dd></div>
    </dl>
    @if($activity->body)<p class="rounded-lg bg-gp-surface-2 p-3">{{ $activity->body }}</p>@endif
</article>
@endsection
