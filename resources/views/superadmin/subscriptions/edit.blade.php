@extends('layouts.superadmin')
@section('title', 'Modifier abonnement')
@section('breadcrumb', 'Billing / Abonnements')
@section('heading', 'Modifier abonnement #'.$subscription->id)
@section('content')
<form method="POST" action="{{ route('superadmin.subscriptions.update', $subscription) }}" class="sa-card max-w-2xl space-y-4">
    @csrf @method('PUT')
    <p class="text-sm text-slate-400">Client : <span class="font-semibold text-white">{{ $subscription->tenant?->name }}</span></p>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs text-slate-500">Plan</label>
            <select name="saas_plan_id" class="sa-select" required>
                @foreach($plans as $p)
                    <option value="{{ $p->id }}" {{ $subscription->saas_plan_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Cycle</label>
            <select name="billing_cycle" class="sa-select">
                <option value="monthly" {{ $subscription->billing_cycle === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                <option value="yearly" {{ $subscription->billing_cycle === 'yearly' ? 'selected' : '' }}>Annuel</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Provider</label>
            <select name="provider" class="sa-select">
                @foreach($providers as $k => $v)
                    <option value="{{ $k }}" {{ $subscription->provider === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="mb-1 block text-xs text-slate-500">Montant</label><input type="number" step="0.01" name="amount" value="{{ $subscription->amount }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Fin</label><input type="datetime-local" name="ends_at" value="{{ optional($subscription->ends_at)->format('Y-m-d\TH:i') }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Renouvellement</label><input type="datetime-local" name="renews_at" value="{{ optional($subscription->renews_at)->format('Y-m-d\TH:i') }}" class="sa-input"></div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="auto_renew" value="1" {{ $subscription->auto_renew ? 'checked' : '' }}> Renouvellement automatique</label>
    <div><label class="mb-1 block text-xs text-slate-500">Notes</label><textarea name="notes" rows="3" class="sa-input">{{ $subscription->notes }}</textarea></div>
    <div class="flex gap-2">
        <button class="sa-btn sa-btn-primary">Enregistrer</button>
        <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="sa-btn sa-btn-ghost">Annuler</a>
    </div>
</form>
@endsection
