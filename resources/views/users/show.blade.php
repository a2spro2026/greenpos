@extends('layouts.app')

@section('title', $user->displayName())
@section('breadcrumb', 'Administration / Utilisateurs')
@section('heading', $user->displayName())
@section('subtitle', ($user->job_title ?: 'Collaborateur') . ' · ' . $user->roleLabel($company))

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('users.print')
            <a href="{{ route('users.print', $user) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('users.update')
            <a href="{{ route('users.edit', $user) }}" class="gp-btn-primary">Modifier</a>
            @if($user->status === 'active')
                <form method="POST" action="{{ route('users.deactivate', $user) }}" onsubmit="return confirm('Désactiver cet utilisateur ?')">@csrf<button class="gp-btn-secondary !text-amber-600">Désactiver</button></form>
            @else
                <form method="POST" action="{{ route('users.reactivate', $user) }}">@csrf<button class="gp-btn-secondary !text-emerald-600">Réactiver</button></form>
            @endif
        @endcan
        @can('users.delete')
            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Archiver cet utilisateur ?')">@csrf @method('DELETE')<button class="gp-btn-secondary !text-rose-600">Supprimer</button></form>
        @endcan
    </div>
@endsection

@section('content')
    @include('users._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap items-center gap-4">
        @if($user->photoUrl())
            <img src="{{ $user->photoUrl() }}" alt="" class="h-16 w-16 rounded-2xl object-cover ring-2 ring-gp-primary/20">
        @else
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gp-primary-soft text-xl font-bold text-gp-primary">{{ $user->initials() }}</span>
        @endif
        <div>
            <p class="font-bold text-gp-text dark:text-white">{{ $user->displayName() }}</p>
            <p class="text-sm text-gp-muted">{{ $user->email }} · {{ $user->phone ?: '—' }}</p>
        </div>
        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->statusColor() }}">{{ $user->statusLabel() }}</span>
        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-white/10">{{ $user->roleLabel($company) }}</span>
    </div>

    @php
        $tabs = [
            'overview' => 'Informations',
            'activity' => 'Activité',
            'permissions' => 'Permissions',
            'stores' => 'Boutiques',
            'history' => 'Historique',
            'documents' => 'Documents',
        ];
    @endphp
    <nav class="mb-6 flex gap-1 overflow-x-auto border-b border-gp-border dark:border-white/10">
        @foreach($tabs as $key => $label)
            <a href="{{ route('users.show', ['user' => $user, 'tab' => $key]) }}"
               class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold transition {{ $tab === $key ? 'border-gp-primary text-gp-primary' : 'border-transparent text-gp-muted hover:text-gp-text' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if($tab === 'overview')
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Profil</h2>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-gp-muted">Prénom</dt><dd class="font-semibold">{{ $user->first_name ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Nom</dt><dd class="font-semibold">{{ $user->last_name ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Email</dt><dd class="font-semibold">{{ $user->email }}</dd></div>
                    <div><dt class="text-gp-muted">Téléphone</dt><dd class="font-semibold">{{ $user->phone ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Username</dt><dd class="font-semibold">{{ $user->username ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Fonction</dt><dd class="font-semibold">{{ $user->job_title ?: '—' }}</dd></div>
                    <div><dt class="text-gp-muted">Département</dt><dd class="font-semibold">{{ $user->departmentLabel() }}</dd></div>
                    <div><dt class="text-gp-muted">Embauche</dt><dd class="font-semibold">{{ optional($user->hired_at)->format('d/m/Y') ?: '—' }}</dd></div>
                </dl>
            </article>
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Accès & connexion</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gp-muted">Rôle</dt><dd class="font-bold">{{ $user->roleLabel($company) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Statut</dt><dd><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->statusColor() }}">{{ $user->statusLabel() }}</span></dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Dernière connexion</dt><dd class="font-semibold">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?: 'Jamais' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Appareil</dt><dd class="font-semibold">{{ $user->last_login_device ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">IP</dt><dd class="font-semibold">{{ $user->last_login_ip ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Créé le</dt><dd class="font-semibold">{{ $user->created_at->format('d/m/Y') }}</dd></div>
                </dl>

                @can('users.reset')
                    <form method="POST" action="{{ route('users.reset-password', $user) }}" class="mt-6 space-y-3 border-t border-gp-border pt-4 dark:border-white/10">
                        @csrf
                        <h3 class="text-sm font-bold">Réinitialiser le mot de passe</h3>
                        <input type="password" name="password" class="gp-input w-full" placeholder="Nouveau mot de passe" required>
                        <input type="password" name="password_confirmation" class="gp-input w-full" placeholder="Confirmation" required>
                        <button class="gp-btn-secondary w-full">Réinitialiser</button>
                    </form>
                @endcan
            </article>
        </div>
    @endif

    @if($tab === 'activity')
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="gp-card overflow-hidden p-0">
                <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Historique des connexions</h2></div>
                @if($user->loginLogs->isEmpty())
                    <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune connexion.</div>
                @else
                    <ul class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($user->loginLogs as $login)
                            <li class="flex items-center justify-between px-5 py-3 text-sm">
                                <div>
                                    <p class="font-semibold">{{ optional($login->logged_in_at)->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-gp-muted">{{ $login->device }} · {{ $login->ip_address }}</p>
                                </div>
                                <span class="text-xs text-gp-muted truncate max-w-[40%]">{{ \Illuminate\Support\Str::limit($login->user_agent, 40) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Dernière session</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gp-muted">Date</dt><dd class="font-semibold">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Appareil</dt><dd class="font-semibold">{{ $user->last_login_device ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gp-muted">Adresse IP</dt><dd class="font-semibold">{{ $user->last_login_ip ?: '—' }}</dd></div>
                </dl>
            </article>
        </div>
    @endif

    @if($tab === 'permissions')
        <section class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10">
                <h2 class="text-sm font-bold">Permissions du rôle « {{ $user->roleLabel($company) }} »</h2>
                <p class="mt-1 text-xs text-gp-muted">Aperçu basé sur la matrice RBAC. Le module Rôles & Permissions permettra une personnalisation fine.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr><th class="px-4 py-3 text-left">Module</th><th class="px-4 py-3 text-left">Action</th><th class="px-4 py-3 text-center">Autorisé</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($permissions as $perm)
                            <tr>
                                <td class="px-4 py-2.5 font-semibold capitalize">{{ $perm['module'] }}</td>
                                <td class="px-4 py-2.5 text-gp-muted">{{ $perm['action'] }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    @if($perm['allowed'])
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Oui</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">Non</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if($tab === 'stores')
        <section class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Boutiques assignées</h2>
            @if($user->stores->isEmpty())
                <p class="text-sm text-gp-muted">Aucune boutique assignée.</p>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($user->stores as $st)
                        <div class="rounded-xl border border-gp-border px-4 py-3 dark:border-white/10">
                            <p class="font-semibold">{{ $st->name }}</p>
                            <p class="text-xs text-gp-muted">{{ $st->city ?: '—' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if($tab === 'history')
        <section class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Journal d'activité</h2></div>
            @if($user->logs->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune entrée.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($user->logs as $log)
                        <li class="flex items-start gap-3 px-5 py-3 text-sm">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold dark:bg-white/10">{{ strtoupper(substr($log->action, 0, 2)) }}</div>
                            <div class="flex-1">
                                <p>{{ $log->message }}</p>
                                <p class="text-xs text-gp-muted">{{ $log->actor?->displayName() ?? 'Système' }} · {{ $log->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if($tab === 'documents')
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="gp-card overflow-hidden p-0 lg:col-span-2">
                <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Documents</h2></div>
                @if($user->documents->isEmpty())
                    <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucun document.</div>
                @else
                    <ul class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($user->documents as $doc)
                            <li class="flex items-center justify-between px-5 py-3 text-sm">
                                <div>
                                    <a href="{{ $doc->url() }}" target="_blank" class="font-semibold text-gp-primary hover:underline">{{ $doc->title }}</a>
                                    <p class="text-xs text-gp-muted">{{ $doc->categoryLabel() }} · {{ $doc->created_at->format('d/m/Y') }}</p>
                                </div>
                                @can('users.update')
                                    <form method="POST" action="{{ route('users.documents.destroy', [$user, $doc]) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="text-rose-600 text-xs font-semibold">Supprimer</button></form>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
            @can('users.update')
                <section class="gp-card">
                    <h2 class="mb-4 text-sm font-bold">Ajouter un document</h2>
                    <form method="POST" action="{{ route('users.documents.store', $user) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div><label class="gp-label">Fichier *</label><input type="file" name="file" class="gp-input w-full" required></div>
                        <div><label class="gp-label">Titre</label><input type="text" name="title" class="gp-input w-full"></div>
                        <div>
                            <label class="gp-label">Catégorie</label>
                            <select name="category" class="gp-select w-full">
                                @foreach(\App\Models\UserDocument::CATEGORIES as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="gp-btn-primary w-full">Uploader</button>
                    </form>
                </section>
            @endcan
        </div>
    @endif
@endsection
