@extends('layouts.superadmin')
@section('title', 'Plan '.$plan->name)
@section('breadcrumb', 'Billing / Plans')
@section('heading', 'Configurer — '.$plan->name)
@section('content')
<form method="POST" action="{{ route('superadmin.plans.update', $plan) }}" class="sa-card max-w-3xl space-y-4">
    @csrf @method('PUT')
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs text-slate-500">Nom</label><input name="name" value="{{ old('name', $plan->name) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Tagline</label><input name="tagline" value="{{ old('tagline', $plan->tagline) }}" class="sa-input"></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs text-slate-500">Description</label><textarea name="description" rows="2" class="sa-input">{{ old('description', $plan->description) }}</textarea></div>
        <div><label class="mb-1 block text-xs text-slate-500">Prix mensuel</label><input type="number" step="0.01" name="price_monthly" value="{{ old('price_monthly', $plan->price_monthly) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Prix annuel</label><input type="number" step="0.01" name="price_yearly" value="{{ old('price_yearly', $plan->price_yearly) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Max utilisateurs</label><input type="number" name="max_users" value="{{ old('max_users', $plan->max_users) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Max boutiques</label><input type="number" name="max_stores" value="{{ old('max_stores', $plan->max_stores) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Stockage (Go)</label><input type="number" name="storage_gb" value="{{ old('storage_gb', $plan->storage_gb) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Jours d'essai</label><input type="number" name="trial_days" value="{{ old('trial_days', $plan->trial_days ?? 14) }}" class="sa-input"></div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Niveau de support</label>
            <select name="support_level" class="sa-select">
                @foreach($supportLevels as $k => $v)
                    <option value="{{ $k }}" {{ ($plan->support_level ?? 'email') === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
        <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2"><input type="checkbox" name="api_enabled" value="1" {{ $plan->api_enabled ? 'checked' : '' }}> API disponible</label>
        <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2"><input type="checkbox" name="support_included" value="1" {{ $plan->support_included ? 'checked' : '' }}> Support inclus</label>
        <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2"><input type="checkbox" name="backups_enabled" value="1" {{ $plan->backups_enabled ? 'checked' : '' }}> Sauvegardes auto</label>
        <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2"><input type="checkbox" name="custom_domain_enabled" value="1" {{ $plan->custom_domain_enabled ? 'checked' : '' }}> Domaine custom</label>
    </div>

    <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Modules inclus</p>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($modules as $key => $label)
                <label class="flex items-center gap-2 rounded-lg border border-white/5 px-3 py-2 text-sm">
                    <input type="checkbox" name="modules[]" value="{{ $key }}" {{ in_array($key, $plan->modules ?? [], true) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">Fonctionnalités (une par ligne)</label>
        <textarea name="features" rows="4" class="sa-input">{{ old('features', implode("\n", $plan->features ?? [])) }}</textarea>
    </div>
    <div class="flex flex-wrap gap-4 text-sm">
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ $plan->is_active ? 'checked' : '' }}> Actif</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_public" value="1" {{ $plan->is_public ? 'checked' : '' }}> Public</label>
    </div>
    <div class="flex gap-2">
        <button class="sa-btn sa-btn-primary">Enregistrer</button>
        <a href="{{ route('superadmin.plans.index') }}" class="sa-btn sa-btn-ghost">Retour</a>
    </div>
</form>
@endsection
