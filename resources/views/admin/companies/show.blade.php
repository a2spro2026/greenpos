@extends('layouts.admin')

@section('title', $company->name)
@section('breadcrumb', 'Entreprises')
@section('heading', $company->name)
@section('actions')
    <a href="{{ route('admin.companies.edit', $company) }}" class="pa-btn pa-btn-ghost">Modifier</a>
            <form method="POST" action="{{ route('admin.companies.impersonate', $company) }}" class="inline">
        @csrf
        <button class="pa-btn pa-btn-primary" type="submit">Se connecter à cette entreprise</button>
    </form>
@endsection

@section('content')
<div class="pa-grid-2 mb-4">
    <div class="pa-card space-y-3 text-sm">
        <h2 class="text-sm font-bold text-white">Fiche entreprise</h2>
        <div class="flex justify-between gap-3"><span class="text-zinc-500">Activité</span><span>{{ $company->activity ?: '—' }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-zinc-500">Email</span><span>{{ $company->email ?: '—' }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-zinc-500">Téléphone</span><span>{{ $company->phone ?: '—' }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-zinc-500">Adresse</span><span class="text-right">{{ $company->address ?: '—' }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-zinc-500">Ville / Pays</span><span>{{ $company->city ?: '—' }} · {{ $company->country ?: '—' }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-zinc-500">Statut</span>
            <span class="pa-badge {{ $company->status === 'active' ? 'pa-badge-ok' : 'pa-badge-danger' }}">{{ $company->status }}</span>
        </div>
        <div class="flex justify-between gap-3"><span class="text-zinc-500">Plan</span><span>{{ $tenant?->currentSubscription?->plan?->name ?? '—' }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-zinc-500">Tenant SaaS</span><span class="pa-mono text-xs">{{ $tenant?->status ?? '—' }}</span></div>
    </div>

    <div class="pa-card space-y-4">
        <h2 class="text-sm font-bold text-white">Actions</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.companies.edit', $company) }}" class="pa-btn pa-btn-ghost">Modifier</a>
            @if($company->status === 'active')
                <form method="POST" action="{{ route('admin.companies.suspend', $company) }}">
                    @csrf
                    <button class="pa-btn pa-btn-warn" type="submit">Suspendre</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.companies.reactivate', $company) }}">
                    @csrf
                    <button class="pa-btn pa-btn-primary" type="submit">Réactiver</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.companies.impersonate', $company) }}">
                @csrf
                <button class="pa-btn pa-btn-ghost" type="submit">Se connecter à cette entreprise</button>
            </form>
            <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" onsubmit="return confirm('Supprimer définitivement cette entreprise ?');">
                @csrf
                @method('DELETE')
                <button class="pa-btn pa-btn-danger" type="submit">Supprimer</button>
            </form>
        </div>

        <div>
            <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-zinc-500">Boutiques</h3>
            <ul class="space-y-1 text-sm">
                @forelse($company->stores as $store)
                    <li class="flex justify-between rounded-lg border border-white/5 px-3 py-2">
                        <span>{{ $store->name }}</span>
                        <span class="text-zinc-500">{{ $store->is_default ? 'Principale' : '' }}</span>
                    </li>
                @empty
                    <li class="text-zinc-500">Aucune boutique</li>
                @endforelse
            </ul>
        </div>

        <div>
            <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-zinc-500">Utilisateurs</h3>
            <ul class="space-y-1 text-sm">
                @forelse($company->users as $user)
                    <li class="flex justify-between rounded-lg border border-white/5 px-3 py-2">
                        <span>{{ $user->name }} <span class="text-zinc-500">({{ $user->pivot->role }})</span></span>
                        <span class="pa-mono text-xs text-zinc-400">{{ $user->email }}</span>
                    </li>
                @empty
                    <li class="text-zinc-500">Aucun utilisateur</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.companies.modules.update', $company) }}" class="pa-card mt-4">
    @csrf
    @method('PUT')
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-white">Modules de l’entreprise</h2>
            <p class="mt-1 text-xs text-zinc-500">Le client ne peut plus modifier ce catalogue. Vous seul pouvez ajouter ou retirer un module.</p>
        </div>
        <button class="pa-btn pa-btn-primary" type="submit">Enregistrer les modules</button>
    </div>
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($catalog as $mod)
            @php
                $locked = !empty($mod['always_on']) || !empty($mod['coming_soon']);
            @endphp
            <label class="flex items-start gap-2 rounded-lg border border-white/5 px-3 py-2 text-sm {{ $locked && ! $mod['is_enabled'] ? 'opacity-50' : '' }}">
                <input
                    type="checkbox"
                    name="modules[]"
                    value="{{ $mod['key'] }}"
                    class="mt-1"
                    @checked($mod['is_enabled'])
                    @disabled($locked)
                >
                @if(!empty($mod['always_on']))
                    <input type="hidden" name="modules[]" value="{{ $mod['key'] }}">
                @endif
                <span>
                    <span class="font-semibold text-zinc-100">{{ $mod['name'] }}</span>
                    <span class="block text-xs text-zinc-500">{{ $mod['category'] }} · {{ $mod['in_plan'] ? 'Inclus plan' : 'Hors plan' }}{{ !empty($mod['coming_soon']) ? ' · Bientôt' : '' }}</span>
                </span>
            </label>
        @endforeach
    </div>
</form>
@endsection
