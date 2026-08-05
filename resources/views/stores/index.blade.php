@extends('layouts.app')

@section('title', 'Liste des boutiques')
@section('breadcrumb', 'Administration / Boutiques')
@section('heading', 'Boutiques')
@section('subtitle', 'Inventaire de vos points de vente et performances.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('stores.print')
            <a href="{{ route('stores.print') }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('stores.export')
            <a href="{{ route('stores.export', request()->only(['q','status','city'])) }}" class="gp-btn-secondary">Export CSV</a>
        @endcan
        @can('stores.create')
            <a href="{{ route('stores.create') }}" class="gp-btn-primary">Nouvelle boutique</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('stores._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('stores.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[200px] flex-1">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="gp-input w-full">
        </div>
        <select name="status" class="gp-select w-36">
            <option value="">Statut</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
        </select>
        <select name="city" class="gp-select w-40">
            <option value="">Ville</option>
            @foreach($cities as $city)
                <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
            @endforeach
        </select>
        <button class="gp-btn-primary">Filtrer</button>
        @if(array_filter($filters ?? []))
            <a href="{{ route('stores.index') }}" class="text-sm text-gp-muted hover:text-gp-text">Effacer</a>
        @endif
    </form>

    <section class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3">Boutique</th>
                        <th class="px-4 py-3">Ville</th>
                        <th class="px-4 py-3">Responsable</th>
                        <th class="px-4 py-3">Téléphone</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3">Utilisateurs</th>
                        <th class="px-4 py-3">Produits</th>
                        <th class="px-4 py-3">CA</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($stores as $store)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($store->logoUrl())
                                        <img src="{{ $store->logoUrl() }}" alt="" class="h-9 w-9 rounded-xl object-cover ring-1 ring-gp-border dark:ring-white/10">
                                    @else
                                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $store->initials() }}</span>
                                    @endif
                                    <div>
                                        <a href="{{ route('stores.show', $store) }}" class="font-semibold text-gp-primary hover:underline">{{ $store->name }}</a>
                                        <p class="text-xs text-gp-muted">{{ $store->code ?: '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gp-muted">{{ $store->city ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $store->manager?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $store->phone ?: '—' }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $store->statusColor() }}">{{ $store->statusLabel() }}</span></td>
                            <td class="px-4 py-3">{{ $store->users_count }}</td>
                            <td class="px-4 py-3">{{ $store->metric_products ?? $store->products_count }}</td>
                            <td class="px-4 py-3 font-semibold">{{ number_format($store->metric_revenue ?? 0, 0, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <a href="{{ route('stores.show', $store) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Voir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @can('stores.update')
                                        <a href="{{ route('stores.edit', $store) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Modifier">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endcan
                                    <form method="POST" action="{{ route('stores.switch', $store) }}">
                                        @csrf
                                        <button type="submit" class="rounded p-1.5 text-gp-primary hover:bg-slate-100 dark:hover:bg-white/10" title="Activer">⇄</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-12 text-center text-gp-muted">Aucune boutique.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($stores->hasPages())
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $stores->links() }}</div>
        @endif
    </section>
@endsection
