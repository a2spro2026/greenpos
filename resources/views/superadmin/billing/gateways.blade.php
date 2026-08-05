@extends('layouts.superadmin')
@section('title', 'Passerelles de paiement')
@section('breadcrumb', 'Billing / Gateways')
@section('heading', 'Connecteurs de paiement')
@section('actions')
    <a href="{{ route('superadmin.billing.dashboard') }}" class="sa-btn sa-btn-ghost">Dashboard billing</a>
@endsection
@section('content')
<p class="mb-6 max-w-2xl text-sm text-slate-400">Architecture extensible Stripe · PayPal · CMI · Manuel. Les clés API sont stockées en base ; en sandbox les charges sont simulées si les credentials manquent.</p>
<div class="grid gap-4 xl:grid-cols-2">
@foreach($board as $gw)
    @php $row = $rows[$gw['code']] ?? null; @endphp
    <article class="sa-card">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-white">{{ $gw['label'] }}</h2>
                <p class="text-xs text-slate-500">code <span class="sa-mono">{{ $gw['code'] }}</span></p>
            </div>
            <span class="sa-badge {{ $gw['enabled'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">{{ strtoupper($gw['status']) }}</span>
        </div>
        @if($row)
        <form method="POST" action="{{ route('superadmin.billing.gateways.update', $row) }}" class="space-y-3">
            @csrf @method('PUT')
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_enabled" value="1" {{ $row->is_enabled ? 'checked' : '' }}> Activée</label>
            <div>
                <label class="mb-1 block text-xs text-slate-500">Mode</label>
                <select name="mode" class="sa-select">
                    <option value="test" {{ $row->mode === 'test' ? 'selected' : '' }}>Test / Sandbox</option>
                    <option value="live" {{ $row->mode === 'live' ? 'selected' : '' }}>Live</option>
                </select>
            </div>
            @if($gw['code'] === 'stripe')
                <div><label class="mb-1 block text-xs text-slate-500">Publishable key</label><input name="public_key" class="sa-input" placeholder="pk_test_…"></div>
                <div><label class="mb-1 block text-xs text-slate-500">Secret key</label><input name="secret_key" class="sa-input" placeholder="sk_test_…"></div>
            @elseif($gw['code'] === 'paypal')
                <div><label class="mb-1 block text-xs text-slate-500">Client ID</label><input name="client_id" class="sa-input"></div>
                <div><label class="mb-1 block text-xs text-slate-500">Client secret</label><input name="client_secret" class="sa-input"></div>
            @elseif($gw['code'] === 'cmi')
                <div><label class="mb-1 block text-xs text-slate-500">Merchant ID</label><input name="merchant_id" class="sa-input"></div>
                <div><label class="mb-1 block text-xs text-slate-500">Store key</label><input name="store_key" class="sa-input"></div>
            @else
                <p class="text-xs text-slate-500">Paiement manuel — aucune clé requise. Enregistrement comptable côté Super Admin.</p>
            @endif
            <div><label class="mb-1 block text-xs text-slate-500">Webhook secret</label><input name="webhook_secret" class="sa-input" value="{{ $row->webhook_secret }}"></div>
            <div><label class="mb-1 block text-xs text-slate-500">Message statut</label><input name="status_message" class="sa-input" value="{{ $row->status_message }}"></div>
            <button class="sa-btn sa-btn-primary">Enregistrer</button>
        </form>
        @endif
    </article>
@endforeach
</div>
@endsection
