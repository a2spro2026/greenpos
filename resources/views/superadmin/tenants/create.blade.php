@extends('layouts.superadmin')
@section('title', 'Nouveau client')
@section('breadcrumb', 'Platform / Clients')
@section('heading', 'Créer un client')
@section('content')
<form method="POST" action="{{ route('superadmin.tenants.store') }}" class="sa-card max-w-2xl space-y-4">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs text-slate-500">Nom</label><input name="name" value="{{ old('name') }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Raison sociale</label><input name="legal_name" value="{{ old('legal_name') }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Email</label><input type="email" name="email" value="{{ old('email') }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Téléphone</label><input name="phone" value="{{ old('phone') }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Pays</label><input name="country" value="{{ old('country', 'MA') }}" maxlength="2" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Ville</label><input name="city" value="{{ old('city') }}" class="sa-input"></div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Plan</label>
            <select name="saas_plan_id" class="sa-select" required>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->priceLabel() }}/mois</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Cycle</label>
            <select name="billing_cycle" class="sa-select">
                <option value="monthly">Mensuel</option>
                <option value="yearly">Annuel</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Fournisseur paiement</label>
            <select name="provider" class="sa-select">
                @foreach($providers as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Statut initial</label>
            <select name="status" class="sa-select">
                <option value="trial">Essai 14 jours</option>
                <option value="active">Actif</option>
            </select>
        </div>
    </div>
    <label class="flex items-center gap-2 text-sm text-slate-300">
        <input type="checkbox" name="provision_company" value="1" class="rounded border-slate-600 bg-slate-900">
        Provisionner aussi une entreprise GreenPOS V1
    </label>
    <div class="flex gap-2">
        <button class="sa-btn sa-btn-primary">Créer</button>
        <a href="{{ route('superadmin.tenants.index') }}" class="sa-btn sa-btn-ghost">Annuler</a>
    </div>
</form>
@endsection
