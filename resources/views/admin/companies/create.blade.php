@extends('layouts.admin')

@section('title', 'Nouvelle entreprise')
@section('breadcrumb', 'Entreprises')
@section('heading', 'Nouvelle entreprise')
@section('actions')
    <a href="{{ route('admin.companies.index') }}" class="pa-btn pa-btn-ghost">Annuler</a>
@endsection

@section('content')
@if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.companies.store') }}" class="pa-card space-y-5 max-w-3xl">
    @csrf
    <p class="text-sm text-zinc-400">Création immédiate (sans validation) : entreprise, boutique principale, compte administrateur, abonnement et modules du plan. Statut <strong>ACTIVE</strong>.</p>

    <div class="pa-grid-2">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Nom de l’entreprise *</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Activité</label>
            <input type="text" name="activity" value="{{ old('activity') }}" class="pa-input" placeholder="Retail, Restauration…">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Responsable *</label>
            <input type="text" name="owner_name" value="{{ old('owner_name') }}" required class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Email *</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Téléphone</label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Plan d’abonnement *</label>
            <select name="saas_plan_id" required class="pa-select">
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected((string) old('saas_plan_id') === (string) $plan->id)>
                        {{ $plan->name }} — {{ number_format((float) $plan->price_monthly, 0, ',', ' ') }} {{ $plan->currency }}/mois
                    </option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Adresse</label>
            <input type="text" name="address" value="{{ old('address') }}" class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Pays</label>
            <input type="text" name="country" value="{{ old('country', 'Maroc') }}" class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Ville</label>
            <input type="text" name="city" value="{{ old('city') }}" class="pa-input">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Mot de passe admin (optionnel)</label>
            <input type="text" name="password" value="{{ old('password') }}" class="pa-input" placeholder="Généré automatiquement si vide">
        </div>
    </div>

    <button type="submit" class="pa-btn pa-btn-primary">Créer l’entreprise</button>
</form>
@endsection
