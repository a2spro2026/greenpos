@extends('layouts.superadmin')
@section('title', 'Nouveau plan')
@section('breadcrumb', 'Billing / Plans')
@section('heading', 'Créer un plan')
@section('content')
<form method="POST" action="{{ route('superadmin.plans.store') }}" class="sa-card max-w-3xl space-y-4">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs text-slate-500">Code</label><input name="code" value="{{ old('code') }}" class="sa-input" placeholder="starter"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Nom</label><input name="name" value="{{ old('name') }}" class="sa-input" required></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs text-slate-500">Tagline</label><input name="tagline" value="{{ old('tagline') }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Prix mensuel</label><input type="number" step="0.01" name="price_monthly" value="{{ old('price_monthly', 0) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Prix annuel</label><input type="number" step="0.01" name="price_yearly" value="{{ old('price_yearly', 0) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Max utilisateurs</label><input type="number" name="max_users" value="{{ old('max_users', 5) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Max boutiques</label><input type="number" name="max_stores" value="{{ old('max_stores', 1) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Stockage (Go)</label><input type="number" name="storage_gb" value="{{ old('storage_gb', 5) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Jours d'essai</label><input type="number" name="trial_days" value="{{ old('trial_days', 14) }}" class="sa-input"></div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Support</label>
            <select name="support_level" class="sa-select">
                @foreach($supportLevels as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
        <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2"><input type="checkbox" name="api_enabled" value="1"> API</label>
        <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2"><input type="checkbox" name="support_included" value="1" checked> Support</label>
        <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2"><input type="checkbox" name="backups_enabled" value="1"> Sauvegardes</label>
        <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2"><input type="checkbox" name="custom_domain_enabled" value="1"> Domaine custom</label>
    </div>
    <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Modules</p>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($modules as $key => $label)
                <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2 text-sm">
                    <input type="checkbox" name="modules[]" value="{{ $key }}"> {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">Fonctionnalités (une par ligne)</label>
        <textarea name="features" rows="3" class="sa-input">{{ old('features') }}</textarea>
    </div>
    <div class="flex gap-2">
        <button class="sa-btn sa-btn-primary">Créer</button>
        <a href="{{ route('superadmin.plans.index') }}" class="sa-btn sa-btn-ghost">Annuler</a>
    </div>
</form>
@endsection
