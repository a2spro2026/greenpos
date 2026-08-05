@extends('layouts.superadmin')
@section('title', 'Nouvel abonnement')
@section('breadcrumb', 'Billing / Abonnements')
@section('heading', 'Créer un abonnement')
@section('content')
<form method="POST" action="{{ route('superadmin.subscriptions.store') }}" class="sa-card max-w-2xl space-y-4">
    @csrf
    <div>
        <label class="mb-1 block text-xs text-slate-500">Client</label>
        <select name="saas_tenant_id" class="sa-select" required>
            @foreach($tenants as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
        </select>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs text-slate-500">Plan</label>
            <select name="saas_plan_id" class="sa-select" required>
                @foreach($plans as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->priceLabel() }}/mois</option>
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
            <label class="mb-1 block text-xs text-slate-500">Provider</label>
            <select name="provider" class="sa-select">
                @foreach($providers as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Statut</label>
            <select name="status" class="sa-select">
                @foreach($statuses as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
        </div>
        <div><label class="mb-1 block text-xs text-slate-500">Montant (optionnel)</label><input type="number" step="0.01" name="amount" class="sa-input" placeholder="Auto selon plan"></div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="auto_renew" value="1" checked> Renouvellement automatique</label>
    <div><label class="mb-1 block text-xs text-slate-500">Notes</label><textarea name="notes" rows="3" class="sa-input"></textarea></div>
    <div class="flex gap-2">
        <button class="sa-btn sa-btn-primary">Créer</button>
        <a href="{{ route('superadmin.subscriptions.index') }}" class="sa-btn sa-btn-ghost">Annuler</a>
    </div>
</form>
@endsection
