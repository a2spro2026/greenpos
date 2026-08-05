@extends('layouts.superadmin')
@section('title', 'Modifier client')
@section('breadcrumb', 'Platform / Clients')
@section('heading', 'Modifier — '.$tenant->name)
@section('content')
<form method="POST" action="{{ route('superadmin.tenants.update', $tenant) }}" class="sa-card max-w-2xl space-y-4">
    @csrf @method('PUT')
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs text-slate-500">Nom</label><input name="name" value="{{ old('name', $tenant->name) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Slug</label><input name="slug" value="{{ old('slug', $tenant->slug) }}" class="sa-input" required></div>
        <div><label class="mb-1 block text-xs text-slate-500">Raison sociale</label><input name="legal_name" value="{{ old('legal_name', $tenant->legal_name) }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Email</label><input type="email" name="email" value="{{ old('email', $tenant->email) }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Téléphone</label><input name="phone" value="{{ old('phone', $tenant->phone) }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Pays</label><input name="country" value="{{ old('country', $tenant->country) }}" class="sa-input" maxlength="2"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Ville</label><input name="city" value="{{ old('city', $tenant->city) }}" class="sa-input"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Domaine principal</label><input name="primary_domain" value="{{ old('primary_domain', $tenant->primary_domain) }}" class="sa-input" placeholder="client.greenpos.app"></div>
        <div><label class="mb-1 block text-xs text-slate-500">Stockage utilisé (Mo)</label><input type="number" name="storage_used_mb" value="{{ old('storage_used_mb', $tenant->storage_used_mb ?? 0) }}" class="sa-input" min="0"></div>
    </div>
    <div class="flex gap-2">
        <button class="sa-btn sa-btn-primary">Enregistrer</button>
        <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="sa-btn sa-btn-ghost">Annuler</a>
    </div>
</form>
@endsection
