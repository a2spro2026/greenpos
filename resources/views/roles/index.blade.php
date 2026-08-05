@extends('layouts.app')

@section('title', 'Liste des rôles')
@section('breadcrumb', 'Administration / Rôles')
@section('heading', 'Rôles')
@section('subtitle', 'Rôles système et rôles personnalisés de l\'entreprise.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('roles.create')
            <a href="{{ route('roles.create') }}" class="gp-btn-primary">Nouveau rôle</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('roles._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('roles.index') }}" class="mb-6 flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher un rôle…" class="gp-input min-w-[220px] flex-1">
        <button class="gp-btn-primary">Filtrer</button>
    </form>

    <section class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3">Rôle</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">Permissions</th>
                        <th class="px-4 py-3 text-right">Utilisateurs</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($roles as $role)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <a href="{{ route('roles.show', $role) }}" class="inline-flex items-center gap-2 font-semibold text-gp-primary hover:underline">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $role->colorClass() }}">{{ $role->name }}</span>
                                </a>
                                <p class="mt-1 text-xs text-gp-muted line-clamp-1">{{ $role->description }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gp-muted">{{ $role->slug }}</td>
                            <td class="px-4 py-3">
                                @if($role->is_super)
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">Super</span>
                                @elseif($role->is_system)
                                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800">Système</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Personnalisé</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold">{{ $role->permissions_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right font-bold">{{ $role->users_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <a href="{{ route('roles.show', $role) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Voir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @can('roles.update')
                                        @unless($role->is_super)
                                            <a href="{{ route('roles.edit', $role) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Modifier">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                        @endunless
                                    @endcan
                                    @can('roles.create')
                                        <form method="POST" action="{{ route('roles.duplicate', $role) }}">@csrf<button class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Dupliquer">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button></form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gp-muted">Aucun rôle.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
