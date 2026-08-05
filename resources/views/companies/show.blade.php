@extends('layouts.app')

@section('title', $company->name)
@section('breadcrumb', 'Entreprises / Fiche')
@section('heading', $company->name)
@section('subtitle', ($company->activity ?: '—').' · '.$company->currency.' · '.($company->country ?: '—'))

@section('actions')
    <div class="flex flex-wrap gap-2">
        @if($company->status === 'active')
            <form method="POST" action="{{ route('companies.switch', $company) }}">@csrf<button class="gp-btn-secondary">Activer</button></form>
        @endif
        <form method="POST" action="{{ route('companies.set-primary', $company) }}">@csrf<button class="gp-btn-secondary">Définir principale</button></form>
        @can('companies.print')
            <a href="{{ route('companies.print-one', $company) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('companies.update')
            <a href="{{ route('companies.edit', $company) }}" class="gp-btn-primary">Modifier</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('companies._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">CA</p><p class="mt-2 text-2xl font-bold">{{ number_format($company->metric_revenue ?? 0, 0, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Boutiques</p><p class="mt-2 text-2xl font-bold">{{ $company->stores_count }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Utilisateurs</p><p class="mt-2 text-2xl font-bold">{{ $company->users_count }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Statut</p><p class="mt-2"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $company->statusColor() }}">{{ $company->statusLabel() }}</span></p></article>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <article class="gp-card xl:col-span-2 space-y-4">
            <div class="flex items-start gap-4">
                @if($company->logoUrl())
                    <img src="{{ $company->logoUrl() }}" class="h-20 w-20 rounded-2xl object-cover" alt="">
                @else
                    <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gp-primary-soft text-xl font-bold text-gp-primary">{{ $company->initials() }}</span>
                @endif
                <div>
                    <p class="text-sm text-gp-muted">{{ $company->legal_name ?: '—' }}</p>
                    <p class="mt-2 text-sm">{{ $company->address }}</p>
                    <p class="text-sm text-gp-muted">{{ collect([$company->city, $company->country])->filter()->implode(', ') }}</p>
                </div>
            </div>
            <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                <div><dt class="text-xs text-gp-muted">Email</dt><dd class="font-semibold">{{ $company->email ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Téléphone</dt><dd class="font-semibold">{{ $company->phone ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Devise</dt><dd class="font-semibold">{{ $company->currency }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Langue</dt><dd class="font-semibold">{{ $company->locale ?: 'fr' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Fuseau</dt><dd class="font-semibold">{{ $company->timezone }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Créée le</dt><dd class="font-semibold">{{ optional($company->created_at)->format('d/m/Y') }}</dd></div>
            </dl>
        </article>

        <article class="gp-card space-y-3">
            <h2 class="text-sm font-bold">Actions</h2>
            @can('companies.update')
                @if($company->status === 'active')
                    <form method="POST" action="{{ route('companies.deactivate', $company) }}">@csrf<button class="gp-btn-secondary w-full justify-center">Désactiver</button></form>
                    <form method="POST" action="{{ route('companies.archive', $company) }}">@csrf<button class="gp-btn-secondary w-full justify-center">Archiver</button></form>
                @elseif($company->status === 'inactive' || $company->status === 'archived')
                    <form method="POST" action="{{ route('companies.activate', $company) }}">@csrf<button class="gp-btn-secondary w-full justify-center">Réactiver</button></form>
                @endif
            @endcan
            @can('companies.delete')
                <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Supprimer (soft) cette entreprise ?')">
                    @csrf @method('DELETE')
                    <button class="w-full rounded-xl px-4 py-2 text-sm font-semibold text-rose-600 ring-1 ring-rose-200 hover:bg-rose-50">Supprimer</button>
                </form>
            @endcan
        </article>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Boutiques</h2></div>
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @forelse($company->stores as $store)
                    <li class="flex justify-between px-5 py-3 text-sm">
                        <span class="font-semibold">{{ $store->name }}</span>
                        <span class="text-gp-muted">{{ $store->city ?: '—' }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gp-muted">Aucune boutique</li>
                @endforelse
            </ul>
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Utilisateurs</h2></div>
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @forelse($company->users as $user)
                    <li class="flex justify-between px-5 py-3 text-sm">
                        <span class="font-semibold">{{ $user->name }}</span>
                        <span class="text-gp-muted">{{ $user->pivot->role ?? '—' }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gp-muted">Aucun utilisateur</li>
                @endforelse
            </ul>
        </article>
    </div>
@endsection
