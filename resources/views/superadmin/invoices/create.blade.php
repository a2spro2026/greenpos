@extends('layouts.superadmin')
@section('title', 'Émettre facture')
@section('breadcrumb', 'Billing / Factures')
@section('heading', 'Émettre une facture SaaS')
@section('content')
<form method="POST" action="{{ route('superadmin.invoices.store') }}" class="sa-card max-w-xl space-y-4">
    @csrf
    <div>
        <label class="mb-1 block text-xs text-slate-500">Abonnement</label>
        <select name="saas_subscription_id" class="sa-select" required>
            <option value="">Choisir…</option>
            @foreach($subscriptions as $s)
                <option value="{{ $s->id }}" {{ (int)$selected === $s->id ? 'selected' : '' }}>
                    {{ $s->tenant?->name }} — {{ $s->plan?->name }} ({{ number_format($s->amount, 0, ',', ' ') }} {{ $s->currency }})
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">Montant HT (optionnel)</label>
        <input type="number" step="0.01" name="amount" class="sa-input" placeholder="Défaut = montant abonnement">
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">Statut</label>
        <select name="status" class="sa-select">
            <option value="issued">Émise</option>
            <option value="draft">Brouillon</option>
        </select>
    </div>
    <button class="sa-btn sa-btn-primary">Créer</button>
</form>
@endsection
