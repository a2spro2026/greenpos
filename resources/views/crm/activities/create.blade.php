@extends('layouts.app')
@section('title', 'Nouvelle activité')
@section('breadcrumb', 'CRM / Activités')
@section('heading', 'Créer une activité')
@section('content')
@include('crm._nav')
<form method="POST" action="{{ route('crm.activities.store') }}" class="gp-card max-w-2xl space-y-4">
@csrf
<div class="grid gap-4 sm:grid-cols-2">
    <div><label class="mb-1 block text-xs text-gp-muted">Type</label><select name="type" class="gp-input">@foreach($types as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Priorité</label><select name="priority" class="gp-input"><option value="normal">Normale</option><option value="high">Haute</option><option value="low">Basse</option></select></div>
    <div class="sm:col-span-2"><label class="mb-1 block text-xs text-gp-muted">Sujet</label><input name="subject" class="gp-input" required></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Lead</label><select name="crm_lead_id" class="gp-input"><option value="">—</option>@foreach($leads as $l)<option value="{{ $l->id }}" {{ (int)$lead_id===$l->id?'selected':'' }}>{{ $l->displayName() }}</option>@endforeach</select></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Opportunité</label><select name="crm_opportunity_id" class="gp-input"><option value="">—</option>@foreach($opportunities as $o)<option value="{{ $o->id }}" {{ (int)$opportunity_id===$o->id?'selected':'' }}>{{ $o->name }}</option>@endforeach</select></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Début</label><input type="datetime-local" name="starts_at" class="gp-input" value="{{ now()->addHour()->format('Y-m-d\TH:i') }}"></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Fin</label><input type="datetime-local" name="ends_at" class="gp-input" value="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}"></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Échéance tâche</label><input type="datetime-local" name="due_at" class="gp-input"></div>
    <div class="sm:col-span-2"><label class="mb-1 block text-xs text-gp-muted">Notes</label><textarea name="body" rows="3" class="gp-input"></textarea></div>
</div>
<button class="gp-btn-primary">Créer</button>
</form>
@endsection
