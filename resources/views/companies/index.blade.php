@extends('layouts.app')

@section('title', 'Liste des entreprises')
@section('breadcrumb', 'Administration / Entreprises')
@section('heading', 'Entreprises')
@section('subtitle', 'Organisations auxquelles vous avez accès.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('companies.print')
            <a href="{{ route('companies.print') }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('companies.export')
            <a href="{{ route('companies.export', request()->only(['q','status','country'])) }}" class="gp-btn-secondary">Export CSV</a>
        @endcan
        @can('companies.create')
            <a href="{{ route('companies.create') }}" class="gp-btn-primary">Nouvelle entreprise</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('companies._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('companies.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[200px] flex-1">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="gp-input w-full">
        </div>
        <select name="status" class="gp-select w-40">
            <option value="">Statut</option>
            @foreach($statuses as $k => $v)
                <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="country" class="gp-select w-40">
            <option value="">Pays</option>
            @foreach($countries as $country)
                <option value="{{ $country }}" @selected(($filters['country'] ?? '') === $country)>{{ $country }}</option>
            @endforeach
        </select>
        <button class="gp-btn-primary">Filtrer</button>
        @if(array_filter($filters ?? []))
            <a href="{{ route('companies.index') }}" class="text-sm text-gp-muted hover:text-gp-text">Effacer</a>
        @endif
    </form>

    <section class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3">Entreprise</th>
                        <th class="px-4 py-3">Secteur</th>
                        <th class="px-4 py-3">Pays</th>
                        <th class="px-4 py-3">Devise</th>
                        <th class="px-4 py-3">Boutiques</th>
                        <th class="px-4 py-3">Utilisateurs</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3">Créée le</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($companies as $company)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($company->logoUrl())
                                        <img src="{{ $company->logoUrl() }}" alt="" class="h-9 w-9 rounded-xl object-cover ring-1 ring-gp-border">
                                    @else
                                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $company->initials() }}</span>
                                    @endif
                                    <div>
                                        <a href="{{ route('companies.show', $company) }}" class="font-semibold text-gp-primary hover:underline">{{ $company->name }}</a>
                                        <p class="text-xs text-gp-muted">{{ $company->legal_name ?: '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gp-muted">{{ $company->activity ?: '—' }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $company->country ?: '—' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $company->currency }}</td>
                            <td class="px-4 py-3">{{ $company->stores_count }}</td>
                            <td class="px-4 py-3">{{ $company->users_count }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $company->statusColor() }}">{{ $company->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-gp-muted">{{ optional($company->created_at)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <a href="{{ route('companies.show', $company) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Voir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @can('companies.update')
                                        <a href="{{ route('companies.edit', $company) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Modifier">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endcan
                                    @if($company->status === 'active')
                                        <form method="POST" action="{{ route('companies.switch', $company) }}">
                                            @csrf
                                            <button type="submit" class="rounded p-1.5 text-gp-primary hover:bg-slate-100 dark:hover:bg-white/10" title="Activer">⇄</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-12 text-center text-gp-muted">Aucune entreprise.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($companies->hasPages())
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $companies->links() }}</div>
        @endif
    </section>
@endsection
