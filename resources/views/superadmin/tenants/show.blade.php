@extends('layouts.superadmin')
@section('title', $tenant->name)
@section('breadcrumb', 'Platform / Entreprises')
@section('heading', $tenant->name)
@section('actions')
    <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="sa-btn sa-btn-ghost">Modifier</a>
    @if($tenant->isSuspended())
        <form method="POST" action="{{ route('superadmin.tenants.reactivate', $tenant) }}">@csrf<button class="sa-btn sa-btn-primary">Réactiver</button></form>
    @else
        <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant) }}" onsubmit="return confirm('Suspendre cette entreprise ?')">@csrf
            <input type="hidden" name="reason" value="Suspension manuelle Super Admin">
            <button class="sa-btn sa-btn-danger">Suspendre</button>
        </form>
    @endif
    @unless($tenant->archived_at)
        <form method="POST" action="{{ route('superadmin.tenants.archive', $tenant) }}" onsubmit="return confirm('Archiver ?')">@csrf
            <button class="sa-btn sa-btn-ghost">Archiver</button>
        </form>
    @endunless
@endsection
@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <span class="sa-badge {{ $tenant->statusColor() }}">{{ $tenant->statusLabel() }}</span>
    @if($tenant->company)<span class="sa-badge bg-violet-500/15 text-violet-300">Lié V1 #{{ $tenant->company_id }}</span>@endif
</div>
<div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <article class="sa-kpi"><p class="text-xs text-slate-500">Utilisateurs</p><p class="mt-1 text-2xl font-bold">{{ $tenant->usersCount() }}</p></article>
    <article class="sa-kpi"><p class="text-xs text-slate-500">Boutiques</p><p class="mt-1 text-2xl font-bold">{{ $tenant->storesCount() }}</p></article>
    <article class="sa-kpi"><p class="text-xs text-slate-500">Stockage</p><p class="mt-1 text-lg font-bold">{{ $tenant->storageLabel() }}</p></article>
    <article class="sa-kpi"><p class="text-xs text-slate-500">Inscription</p><p class="mt-1 text-lg font-bold">{{ $tenant->created_at->format('d/m/Y') }}</p></article>
</div>
<div class="grid gap-4 xl:grid-cols-3">
    <article class="sa-card xl:col-span-2 space-y-3 text-sm">
        <h2 class="text-sm font-bold text-white">Fiche entreprise</h2>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-slate-500">Email</dt><dd>{{ $tenant->email ?: '—' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Téléphone</dt><dd>{{ $tenant->phone ?: '—' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Localisation</dt><dd>{{ $tenant->city }} · {{ $tenant->country }}</dd></div>
            <div><dt class="text-xs text-slate-500">Domaine</dt><dd class="sa-mono text-xs">{{ $tenant->domainLabel() }}</dd></div>
            <div><dt class="text-xs text-slate-500">Slug</dt><dd class="sa-mono text-xs">{{ $tenant->slug }}</dd></div>
            <div><dt class="text-xs text-slate-500">Plan</dt><dd>{{ $tenant->currentSubscription?->plan?->name ?? '—' }}</dd></div>
            @if($tenant->suspend_reason)<div class="sm:col-span-2"><dt class="text-xs text-slate-500">Motif suspension</dt><dd class="text-amber-300">{{ $tenant->suspend_reason }}</dd></div>@endif
        </dl>
        <h3 class="pt-4 text-sm font-bold text-white">Abonnements</h3>
        <div class="overflow-x-auto">
            <table class="sa-table">
                <thead><tr><th>Plan</th><th>Statut</th><th>Montant</th><th>Renouvellement</th></tr></thead>
                <tbody>
                @foreach($tenant->subscriptions as $s)
                    <tr>
                        <td><a href="{{ route('superadmin.subscriptions.show', $s) }}" class="text-sky-300 hover:underline">{{ $s->plan?->name }}</a></td>
                        <td><span class="sa-badge {{ $s->statusColor() }}">{{ $s->statusLabel() }}</span></td>
                        <td>{{ number_format($s->amount, 2, ',', ' ') }} {{ $s->currency }} / {{ $s->billing_cycle }}</td>
                        <td class="text-slate-400">{{ optional($s->renews_at)->format('d/m/Y') ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </article>
    <aside class="space-y-4">
        <article class="sa-card">
            <h2 class="mb-3 text-sm font-bold text-white">Licences</h2>
            @forelse($tenant->licenses as $lic)
                <p class="sa-mono mb-2 text-xs text-slate-300">{{ $lic->license_key }}</p>
                <p class="mb-2 text-xs text-slate-500">{{ $lic->statusLabel() }}</p>
            @empty
                <p class="text-xs text-slate-500">Aucune</p>
            @endforelse
        </article>
        <article class="sa-card">
            <h2 class="mb-3 text-sm font-bold text-white">Domaines</h2>
            @forelse($tenant->domains as $d)
                <p class="mb-1 text-sm">{{ $d->domain }} <span class="sa-badge bg-slate-500/20 text-slate-300">{{ $d->statusLabel() }}</span></p>
            @empty
                <p class="text-xs text-slate-500">Aucun domaine</p>
            @endforelse
        </article>
        <form method="POST" action="{{ route('superadmin.tenants.destroy', $tenant) }}" onsubmit="return confirm('Supprimer définitivement ?')">
            @csrf @method('DELETE')
            <button class="sa-btn sa-btn-danger w-full">Supprimer l’entreprise</button>
        </form>
    </aside>
</div>
@endsection
