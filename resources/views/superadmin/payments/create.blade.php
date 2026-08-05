@extends('layouts.superadmin')
@section('title', 'Nouveau paiement')
@section('breadcrumb', 'Platform / Paiements')
@section('heading', 'Enregistrer un paiement')
@section('content')
<form method="POST" action="{{ route('superadmin.payments.store') }}" class="sa-card max-w-xl space-y-4">
    @csrf
    <div>
        <label class="mb-1 block text-xs text-slate-500">Client</label>
        <select name="saas_tenant_id" class="sa-select" required>
            @foreach($tenants as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
        </select>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs text-slate-500">Provider</label>
            <select name="provider" class="sa-select">
                @foreach($providers as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Statut</label>
            <select name="status" class="sa-select">
                <option value="paid">Payé</option>
                <option value="pending">En attente</option>
                <option value="failed">Échoué</option>
            </select>
        </div>
        <div><label class="mb-1 block text-xs text-slate-500">Montant</label><input type="number" step="0.01" name="amount" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Devise</label><input name="currency" value="MAD" maxlength="3" class="sa-input" required></div>
    </div>
    <div><label class="mb-1 block text-xs text-slate-500">Description</label><input name="description" class="sa-input" placeholder="Renouvellement Business"></div>
    <div class="flex gap-2">
        <button class="sa-btn sa-btn-primary">Enregistrer</button>
        <a href="{{ route('superadmin.payments.index') }}" class="sa-btn sa-btn-ghost">Annuler</a>
    </div>
</form>
@endsection
