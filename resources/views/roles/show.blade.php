@extends('layouts.app')

@section('title', $role->name)
@section('breadcrumb', 'Administration / Rôles')
@section('heading', $role->name)
@section('subtitle', $role->description ?: $role->slug)

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('roles.create')
            <form method="POST" action="{{ route('roles.duplicate', $role) }}">@csrf<button class="gp-btn-secondary">Dupliquer</button></form>
        @endcan
        @can('roles.update')
            @unless($role->is_super)
                <a href="{{ route('roles.edit', $sourceRole) }}" class="gp-btn-primary">Modifier</a>
            @endunless
        @endcan
        @can('roles.delete')
            @if($role->isDeletable())
                <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Supprimer ce rôle ?')">@csrf @method('DELETE')<button class="gp-btn-secondary !text-rose-600">Supprimer</button></form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    @include('roles._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap items-center gap-3">
        <span class="inline-flex rounded-full px-3 py-1.5 text-sm font-semibold {{ $role->colorClass() }}">{{ $role->name }}</span>
        <span class="font-mono text-xs text-gp-muted">{{ $role->slug }}</span>
        @if($role->is_super)
            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">Super Admin</span>
        @elseif($role->is_system)
            <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800">Système</span>
        @else
            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Personnalisé</span>
        @endif
    </div>

    @php
        $tabs = [
            'overview' => 'Informations',
            'permissions' => 'Permissions',
            'users' => 'Utilisateurs',
            'history' => 'Historique',
        ];
    @endphp
    <nav class="mb-6 flex gap-1 overflow-x-auto border-b border-gp-border dark:border-white/10">
        @foreach($tabs as $key => $label)
            <a href="{{ route('roles.show', ['role' => $role, 'tab' => $key]) }}"
               class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $tab === $key ? 'border-gp-primary text-gp-primary' : 'border-transparent text-gp-muted hover:text-gp-text' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if($tab === 'overview')
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Détails</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gp-muted">Nom</dt><dd class="font-semibold">{{ $role->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Slug</dt><dd class="font-mono text-xs">{{ $role->slug }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Permissions</dt><dd class="font-bold">{{ $role->permissions->count() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Utilisateurs</dt><dd class="font-bold">{{ $users->count() }}</dd></div>
                    <div><dt class="text-gp-muted">Description</dt><dd class="mt-1">{{ $role->description ?: '—' }}</dd></div>
                </dl>
            </article>
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Périmètre spécial actif</h2>
                @php $scopes = $role->permissions->where('group', 'scope'); @endphp
                @if($scopes->isEmpty())
                    <p class="text-sm text-gp-muted">Aucune restriction de périmètre.</p>
                @else
                    <ul class="space-y-2">
                        @foreach($scopes as $s)
                            <li class="flex items-center gap-2 text-sm">
                                <span class="h-2 w-2 rounded-full bg-gp-primary"></span>
                                {{ $s->label }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </div>
    @endif

    @if($tab === 'permissions')
        <section class="gp-card overflow-x-auto p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Matrice (lecture seule)</h2></div>
            <table class="min-w-full text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left">Module</th>
                        @foreach($matrix['actions'] as $ak => $al)
                            <th class="px-2 py-3 text-center">{{ $al }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($matrix['modules'] as $module => $moduleActions)
                        <tr>
                            <td class="px-4 py-2.5 font-semibold">{{ $matrix['moduleLabels'][$module] ?? $module }}</td>
                            @foreach($matrix['actions'] as $ak => $al)
                                <td class="px-2 py-2.5 text-center">
                                    @if(in_array($ak, $moduleActions, true))
                                        @php $key = $module.'.'.$ak; @endphp
                                        @if(in_array($key, $matrix['selected'], true))
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">✓</span>
                                        @else
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-400 text-xs">·</span>
                                        @endif
                                    @else
                                        <span class="text-gp-muted/30">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if($tab === 'users')
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="gp-card overflow-hidden p-0 lg:col-span-2">
                <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Utilisateurs assignés ({{ $users->count() }})</h2></div>
                @if($users->isEmpty())
                    <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucun utilisateur avec ce rôle.</div>
                @else
                    <ul class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($users as $u)
                            <li class="flex items-center justify-between px-5 py-3 text-sm">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $u->initials() }}</span>
                                    <div>
                                        <a href="{{ route('users.show', $u) }}" class="font-semibold text-gp-primary hover:underline">{{ $u->displayName() }}</a>
                                        <p class="text-xs text-gp-muted">{{ $u->email }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $u->statusColor() }}">{{ $u->statusLabel() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @can('roles.update')
                @unless($role->is_super)
                <section class="gp-card">
                    <h2 class="mb-4 text-sm font-bold">Affecter des utilisateurs</h2>
                    <form method="POST" action="{{ route('roles.assign', $role) }}" class="space-y-3">
                        @csrf
                        <div class="max-h-64 space-y-2 overflow-y-auto">
                            @foreach($allUsers as $u)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="rounded border-gp-border text-gp-primary"
                                           {{ $users->contains('id', $u->id) ? 'checked' : '' }}>
                                    {{ $u->displayName() }}
                                </label>
                            @endforeach
                        </div>
                        <button class="gp-btn-primary w-full">Appliquer</button>
                        <p class="text-xs text-gp-muted">Les utilisateurs cochés recevront ce rôle (remplace leur rôle actuel).</p>
                    </form>
                </section>
                @endunless
            @endcan
        </div>
    @endif

    @if($tab === 'history')
        <section class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Journal</h2></div>
            @if($role->logs->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune entrée.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($role->logs as $log)
                        <li class="px-5 py-3 text-sm">
                            <p>{{ $log->message }}</p>
                            <p class="text-xs text-gp-muted">{{ $log->user?->displayName() ?? 'Système' }} · {{ $log->created_at->format('d/m/Y H:i') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
@endsection
