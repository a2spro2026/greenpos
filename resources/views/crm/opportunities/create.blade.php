@extends('layouts.app')
@section('title', 'Nouvelle opportunité')
@section('breadcrumb', 'CRM / Opportunités')
@section('heading', 'Créer une opportunité')
@section('content')
@include('crm._nav')
<form method="POST" action="{{ route('crm.opportunities.store') }}" class="gp-card max-w-2xl space-y-4">
@csrf
<div><label class="mb-1 block text-xs text-gp-muted">Nom</label><input name="name" class="gp-input" required></div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="mb-1 block text-xs text-gp-muted">Lead</label>
        <select name="crm_lead_id" class="gp-input">
            <option value="">—</option>
            @foreach($leads as $l)<option value="{{ $l->id }}" {{ (int)$lead_id === $l->id ? 'selected' : '' }}>{{ $l->displayName() }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs text-gp-muted">Client</label>
        <select name="customer_id" class="gp-input">
            <option value="">—</option>
            @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div><label class="mb-1 block text-xs text-gp-muted">Étape</label><select name="stage" class="gp-input">@foreach($stages as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Montant</label><input type="number" step="0.01" name="amount" class="gp-input" value="0"></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Close prévue</label><input type="date" name="expected_close_on" class="gp-input" value="{{ now()->addDays(30)->toDateString() }}"></div>
    <div><label class="mb-1 block text-xs text-gp-muted">Owner</label><select name="owner_user_id" class="gp-input"><option value="">Moi</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name ?: $u->email }}</option>@endforeach</select></div>
</div>
<div><label class="mb-1 block text-xs text-gp-muted">Description</label><textarea name="description" rows="3" class="gp-input"></textarea></div>
<button class="gp-btn-primary">Créer</button>
</form>
@endsection
