@extends('layouts.app')

@section('title', 'Liste des notifications')
@section('breadcrumb', 'Notifications / Liste')
@section('heading', 'Notifications')
@section('subtitle', 'Recherchez, filtrez et gérez vos alertes.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('notifications.update')
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">@csrf<button class="gp-btn-secondary">Tout marquer lu</button></form>
        @endcan
    </div>
@endsection

@section('content')
    @include('notifications._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('notifications.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="gp-input w-full">
        </div>
        <select name="type" class="gp-select w-36">
            <option value="">Type</option>
            @foreach($types as $k => $v)<option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $v }}</option>@endforeach
        </select>
        <select name="priority" class="gp-select w-36">
            <option value="">Priorité</option>
            @foreach($priorities as $k => $v)<option value="{{ $k }}" @selected(($filters['priority'] ?? '') === $k)>{{ $v }}</option>@endforeach
        </select>
        <select name="status" class="gp-select w-36">
            <option value="">Statut</option>
            @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $v }}</option>@endforeach
        </select>
        <select name="category" class="gp-select w-44">
            <option value="">Catégorie</option>
            @foreach($categories as $k => $v)<option value="{{ $k }}" @selected(($filters['category'] ?? '') === $k)>{{ $v }}</option>@endforeach
        </select>
        <button class="gp-btn-primary">Filtrer</button>
    </form>

    <section class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 w-12"></th>
                        <th class="px-4 py-3">Titre</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Priorité</th>
                        <th class="px-4 py-3">Destinataire</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($notifications as $n)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5 {{ $n->isUnread() ? 'bg-gp-primary-soft/30' : '' }}">
                            <td class="px-4 py-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $n->typeColor() }} text-[10px] font-bold uppercase">{{ substr($n->type, 0, 1) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('notifications.show', $n) }}" class="font-semibold hover:text-gp-primary">{{ $n->title }}</a>
                                <p class="mt-0.5 line-clamp-1 text-xs text-gp-muted">{{ $n->body }}</p>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $n->typeColor() }}">{{ $n->typeLabel() }}</span></td>
                            <td class="px-4 py-3 text-gp-muted">{{ $n->priorityLabel() }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $n->user?->name ?? 'Tous' }}</td>
                            <td class="px-4 py-3 text-gp-muted whitespace-nowrap">{{ $n->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-xs font-semibold">{{ $n->statusLabel() }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    @can('notifications.update')
                                        @if($n->isUnread())
                                            <form method="POST" action="{{ route('notifications.mark-read', $n) }}">@csrf<button class="rounded px-2 py-1 text-xs font-semibold text-gp-primary hover:bg-slate-100 dark:hover:bg-white/10" title="Marquer lu">Lu</button></form>
                                        @endif
                                    @endcan
                                    @can('notifications.archive')
                                        @if($n->status !== 'archived')
                                            <form method="POST" action="{{ route('notifications.archive', $n) }}">@csrf<button class="rounded px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-slate-100 dark:hover:bg-white/10">Archiver</button></form>
                                        @endif
                                    @endcan
                                    @can('notifications.delete')
                                        <form method="POST" action="{{ route('notifications.destroy', $n) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="rounded px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-slate-100 dark:hover:bg-white/10">Suppr.</button></form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-gp-muted">Aucune notification.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notifications->hasPages())
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $notifications->links() }}</div>
        @endif
    </section>
@endsection
