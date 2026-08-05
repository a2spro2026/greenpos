@extends('layouts.app')

@section('title', 'Liste des utilisateurs')
@section('breadcrumb', 'Administration / Utilisateurs')
@section('heading', 'Utilisateurs')
@section('subtitle', 'Gérez les collaborateurs, rôles et accès boutiques.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('users.export')
            <a href="{{ route('users.export', request()->only(['status','role','store_id','department'])) }}" class="gp-btn-secondary">Exporter CSV</a>
        @endcan
        @can('users.create')
            <a href="{{ route('users.create') }}" class="gp-btn-primary">Nouvel utilisateur</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('users._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('users.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[200px] flex-1">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="gp-input w-full">
        </div>
        <select name="status" class="gp-select w-36">
            <option value="">Statut</option>
            @foreach($statuses as $k => $v)<option value="{{ $k }}" {{ ($filters['status'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
        </select>
        <select name="role" class="gp-select w-40">
            <option value="">Rôle</option>
            @foreach($roles as $k => $v)<option value="{{ $k }}" {{ ($filters['role'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
        </select>
        <select name="store_id" class="gp-select w-40">
            <option value="">Boutique</option>
            @foreach($stores as $st)<option value="{{ $st->id }}" {{ ($filters['store_id'] ?? '') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>@endforeach
        </select>
        <select name="department" class="gp-select w-40">
            <option value="">Département</option>
            @foreach($departments as $k => $v)<option value="{{ $k }}" {{ ($filters['department'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
        </select>
        <button class="gp-btn-primary">Filtrer</button>
        @if(array_filter($filters ?? []))
            <a href="{{ route('users.index') }}" class="text-sm text-gp-muted hover:text-gp-text">Effacer</a>
        @endif
    </form>

    <section class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3">Utilisateur</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Téléphone</th>
                        <th class="px-4 py-3">Fonction</th>
                        <th class="px-4 py-3">Boutique</th>
                        <th class="px-4 py-3">Rôle</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3">Dernière connexion</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($user->photoUrl())
                                        <img src="{{ $user->photoUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-white dark:ring-white/10">
                                    @else
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $user->initials() }}</span>
                                    @endif
                                    <a href="{{ route('users.show', $user) }}" class="font-semibold text-gp-primary hover:underline">{{ $user->displayName() }}</a>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gp-muted">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $user->phone ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $user->job_title ?: '—' }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $user->stores->pluck('name')->take(2)->implode(', ') ?: '—' }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold dark:bg-white/10">{{ $user->roleLabel($company) }}</span></td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->statusColor() }}">{{ $user->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-gp-muted">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?: 'Jamais' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <a href="{{ route('users.show', $user) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Voir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @can('users.update')
                                        <a href="{{ route('users.edit', $user) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Modifier">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endcan
                                    @can('users.print')
                                        <a href="{{ route('users.print', $user) }}" target="_blank" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Imprimer">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-12 text-center text-gp-muted">Aucun utilisateur trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
