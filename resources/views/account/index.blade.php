@extends('layouts.app')
@section('title', 'Mon compte')
@section('breadcrumb', 'Session')
@section('heading', 'Mon compte')
@section('subtitle', 'Identité, espace de travail et sécurité de session.')
@section('actions')
    <a href="{{ route('account.preferences') }}" class="gp-btn-secondary">Préférences</a>
    <form method="POST" action="{{ route('session.lock.store') }}" class="inline">@csrf<button class="gp-btn-secondary">Verrouiller</button></form>
@endsection
@section('content')
<section class="grid gap-4 lg:grid-cols-3">
    <article class="gp-card lg:col-span-1">
        <div class="flex flex-col items-center text-center">
            <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-800 text-2xl font-bold text-white shadow-lg">
                @if($user->photoUrl())
                    <img src="{{ $user->photoUrl() }}" alt="" class="h-full w-full object-cover">
                @else
                    {{ $user->initials() }}
                @endif
            </div>
            <h2 class="mt-4 text-lg font-bold">{{ $user->displayName() }}</h2>
            <p class="text-sm text-gp-muted">{{ $user->email }}</p>
            <span class="mt-3 rounded-full bg-gp-primary-soft px-3 py-1 text-xs font-semibold text-gp-primary">{{ $role }}</span>
        </div>
        <dl class="mt-6 space-y-3 border-t border-gp-border pt-4 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-gp-muted">Entreprise</dt><dd class="font-semibold text-right">{{ $company?->name ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gp-muted">Boutique</dt><dd class="font-semibold text-right">{{ $store?->name ?? 'Toutes' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gp-muted">Statut</dt><dd class="font-semibold">{{ $user->statusLabel() }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gp-muted">Dernière connexion</dt><dd class="text-right text-xs">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>
    </article>

    <article class="gp-card lg:col-span-2">
        <h3 class="text-sm font-bold">Sécurité de session</h3>
        <p class="mt-1 text-sm text-gp-muted">Expiration après {{ $sessionLifetime }} minutes d’inactivité. Verrouillage recommandé après {{ $lockIdle }} min.</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-gp-border bg-gp-bg/50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Durée de session</p>
                <p class="mt-1 text-2xl font-bold">{{ $sessionLifetime }} <span class="text-sm font-medium text-gp-muted">min</span></p>
            </div>
            <div class="rounded-xl border border-gp-border bg-gp-bg/50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">IP dernière connexion</p>
                <p class="mt-1 text-lg font-bold">{{ $user->last_login_ip ?: '—' }}</p>
            </div>
        </div>
        <div class="mt-6 flex flex-wrap gap-2">
            @can('users.view')
                <a href="{{ route('users.show', $user) }}" class="gp-btn-secondary">Mon profil détaillé</a>
            @endcan
            <a href="{{ route('account.preferences') }}" class="gp-btn-secondary">Préférences</a>
            <button type="button" class="gp-btn-secondary" data-logout-open>Se déconnecter</button>
        </div>
    </article>
</section>
@endsection
