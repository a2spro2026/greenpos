@extends('layouts.app')
@section('title', 'Nouveau lead')
@section('breadcrumb', 'CRM / Leads')
@section('heading', 'Créer un lead / prospect')
@section('content')
@include('crm._nav')
<form method="POST" action="{{ route('crm.leads.store') }}" class="gp-card max-w-3xl space-y-4">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs text-gp-muted">Type</label>
            <select name="type" class="gp-input">@foreach($types as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-gp-muted">Source</label>
            <select name="source" class="gp-input">@foreach($sources as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-gp-muted">Température</label>
            <select name="rating" class="gp-input">@foreach($ratings as $k=>$v)<option value="{{ $k }}" {{ $k==='warm'?'selected':'' }}>{{ $v }}</option>@endforeach</select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-gp-muted">Commercial</label>
            <select name="owner_user_id" class="gp-input">
                <option value="">Moi</option>
                @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name ?: $u->email }}</option>@endforeach
            </select>
        </div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs text-gp-muted">Société</label><input name="company_name" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Prénom</label><input name="first_name" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Nom</label><input name="last_name" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Email</label><input type="email" name="email" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Téléphone</label><input name="phone" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Ville</label><input name="city" class="gp-input"></div>
        <div><label class="mb-1 block text-xs text-gp-muted">Valeur estimée</label><input type="number" step="0.01" name="estimated_value" class="gp-input" value="0"></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs text-gp-muted">Notes</label><textarea name="description" rows="3" class="gp-input"></textarea></div>
    </div>
    <div class="flex gap-2">
        <button class="gp-btn-primary">Créer</button>
        <a href="{{ route('crm.leads.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>
@endsection
