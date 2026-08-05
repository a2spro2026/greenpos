@extends('layouts.app')
@section('title', 'Configuration')
@section('breadcrumb', 'Onboarding')
@section('heading', 'Assistant de configuration')
@section('subtitle', 'Personnalisez votre espace — moins d’une minute.')
@section('actions')
    <form method="POST" action="{{ route('onboarding.wizard.skip') }}">@csrf<button class="gp-btn-ghost">Passer pour l’instant</button></form>
@endsection
@section('content')
<section class="ob-wizard">
    <div class="ob-wizard-progress">
        <span class="done">Inscription</span>
        <span class="done">Plan {{ $plan?->name }}</span>
        <span class="active">Configuration</span>
        <span>Dashboard</span>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('onboarding.wizard.store') }}" enctype="multipart/form-data" class="gp-card space-y-6">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm font-semibold">Logo
                <input type="file" name="logo" accept="image/*" class="mt-1 block w-full text-sm">
            </label>
            <label class="block text-sm font-semibold">Nom de la caisse
                <input type="text" name="register_name" value="{{ old('register_name', 'Caisse 1') }}" required class="gp-input mt-1">
            </label>
            <label class="block text-sm font-semibold md:col-span-2">Adresse
                <input type="text" name="address" value="{{ old('address') }}" class="gp-input mt-1" placeholder="Rue, quartier…">
            </label>
            <label class="block text-sm font-semibold">Pays
                <input type="text" name="country" value="{{ old('country', $company->country ?? 'Maroc') }}" required class="gp-input mt-1">
            </label>
            <label class="block text-sm font-semibold">Ville
                <input type="text" name="city" value="{{ old('city', $company->city) }}" required class="gp-input mt-1">
            </label>
            <label class="block text-sm font-semibold">Devise
                <select name="currency" class="gp-input mt-1" required>
                    @foreach(['MAD','EUR','USD'] as $c)
                        <option value="{{ $c }}" @selected(old('currency', $company->currency ?? 'MAD') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-semibold">TVA (%)
                <input type="number" step="0.01" name="tax_rate" value="{{ old('tax_rate', 20) }}" required class="gp-input mt-1">
            </label>
        </div>

        <div class="border-t border-gp-border pt-4">
            <h2 class="text-sm font-bold">Catalogue de démarrage</h2>
            <div class="mt-3 grid gap-4 md:grid-cols-3">
                <label class="block text-sm font-semibold">Première catégorie
                    <input type="text" name="category_name" value="{{ old('category_name', 'Général') }}" required class="gp-input mt-1">
                </label>
                <label class="block text-sm font-semibold">Premier produit <span class="font-normal text-gp-muted">(optionnel)</span>
                    <input type="text" name="product_name" value="{{ old('product_name') }}" class="gp-input mt-1" placeholder="Ex. Produit démo">
                </label>
                <label class="block text-sm font-semibold">Prix de vente
                    <input type="number" step="0.01" name="product_price" value="{{ old('product_price', 100) }}" class="gp-input mt-1">
                </label>
            </div>
        </div>

        <div class="border-t border-gp-border pt-4">
            <h2 class="text-sm font-bold">Premier employé <span class="font-normal text-gp-muted">(optionnel)</span></h2>
            <div class="mt-3 grid gap-4 md:grid-cols-3">
                <label class="block text-sm font-semibold">Nom
                    <input type="text" name="employee_name" value="{{ old('employee_name') }}" class="gp-input mt-1">
                </label>
                <label class="block text-sm font-semibold">E-mail
                    <input type="email" name="employee_email" value="{{ old('employee_email') }}" class="gp-input mt-1">
                </label>
                <label class="block text-sm font-semibold">Rôle
                    <select name="employee_role" class="gp-input mt-1">
                        <option value="cashier">Caissier</option>
                        <option value="sales">Commercial</option>
                        <option value="manager">Manager</option>
                        <option value="employee">Employé</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 pt-2">
            <button type="submit" class="gp-btn-primary">Terminer et ouvrir le dashboard</button>
        </div>
    </form>
</section>
<style>
.ob-wizard-progress { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.25rem; }
.ob-wizard-progress span { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:.35rem .7rem; border-radius:999px; background:var(--color-gp-bg); color:var(--color-gp-muted); }
.ob-wizard-progress span.done { background:rgba(16,185,129,.12); color:#059669; }
.ob-wizard-progress span.active { background:rgba(13,148,136,.15); color:#0f766e; }
.gp-input { width:100%; border-radius:.75rem; border:1px solid var(--color-gp-border); background:var(--color-gp-surface); padding:.65rem .85rem; font-size:.875rem; }
</style>
@endsection
