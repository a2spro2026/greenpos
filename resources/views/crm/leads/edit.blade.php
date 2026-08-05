@extends('layouts.app')
@section('title', 'Modifier lead')
@section('breadcrumb', 'CRM / Leads')
@section('heading', 'Modifier — '.$lead->displayName())
@section('content')
@include('crm._nav')
<form method="POST" action="{{ route('crm.leads.update', $lead) }}" class="gp-card max-w-3xl space-y-4">
    @csrf @method('PUT')
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs text-gp-muted">Type</label><select name="type" class="gp-input">@foreach($types as $k=>$v)<option value="{{ $k }}" {{ $lead->type===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Statut</label><select name="status" class="gp-input">@foreach($statuses as $k=>$v)<option value="{{ $k }}" {{ $lead->status===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Source</label><select name="source" class="gp-input">@foreach($sources as $k=>$v)<option value="{{ $k }}" {{ $lead->source===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Température</label><select name="rating" class="gp-input">@foreach($ratings as $k=>$v)<option value="{{ $k }}" {{ $lead->rating===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Score</label><input type="number" name="score" min="0" max="100" value="{{ $lead->score }}" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Commercial</label><select name="owner_user_id" class="gp-input">@foreach($users as $u)<option value="{{ $u->id }}" {{ (int)$lead->owner_user_id===$u->id?'selected':'' }}>{{ $u->name ?: $u->email }}</option>@endforeach</select></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs text-gp-muted">Société</label><input name="company_name" value="{{ $lead->company_name }}" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Prénom</label><input name="first_name" value="{{ $lead->first_name }}" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Nom</label><input name="last_name" value="{{ $lead->last_name }}" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Email</label><input type="email" name="email" value="{{ $lead->email }}" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Téléphone</label><input name="phone" value="{{ $lead->phone }}" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Ville</label><input name="city" value="{{ $lead->city }}" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Valeur estimée</label><input type="number" step="0.01" name="estimated_value" value="{{ $lead->estimated_value }}" class="gp-input"></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs text-gp-muted">Notes</label><textarea name="description" rows="3" class="gp-input">{{ $lead->description }}</textarea></div>
    </div>
    <div class="flex gap-2"><button class="gp-btn-primary">Enregistrer</button><a href="{{ route('crm.leads.show', $lead) }}" class="gp-btn-secondary">Annuler</a></div>
</form>
@endsection
