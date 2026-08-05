@extends('layouts.app')

@section('title', 'Rôles & Permissions')
@section('breadcrumb', 'Administration / Rôles')
@section('heading', 'Dashboard RBAC')
@section('subtitle', 'Contrôle d\'accès basé sur les rôles — pilotage des permissions GreenPOS.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('roles.export')
            <a href="{{ route('roles.export') }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        <a href="{{ route('roles.matrix') }}" class="gp-btn-secondary">Matrice</a>
        @can('roles.create')
            <a href="{{ route('roles.create') }}" class="gp-btn-primary">Nouveau rôle</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('roles._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Rôles</p><p class="mt-2 text-3xl font-bold">{{ $stats['roles'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Système</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['system'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Personnalisés</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['custom'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Permissions</p><p class="mt-2 text-3xl font-bold">{{ $stats['permissions'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Utilisateurs</p><p class="mt-2 text-3xl font-bold">{{ $stats['users'] }}</p></article>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($roles as $role)
            <a href="{{ route('roles.show', $role) }}" class="gp-card group transition hover:ring-2 hover:ring-gp-primary/30">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $role->colorClass() }}">{{ $role->name }}</span>
                        <p class="mt-2 text-xs text-gp-muted">{{ $role->slug }}</p>
                    </div>
                    @if($role->is_system)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600 dark:bg-white/10">Système</span>
                    @endif
                </div>
                <p class="mt-3 line-clamp-2 text-sm text-gp-muted">{{ $role->description ?: '—' }}</p>
                <div class="mt-4 flex gap-4 text-xs font-semibold">
                    <span>{{ $role->permissions_count ?? $role->permissions()->count() }} permissions</span>
                    <span class="text-gp-muted">{{ $role->users_count ?? 0 }} utilisateurs</span>
                </div>
            </a>
        @endforeach
    </section>
@endsection
