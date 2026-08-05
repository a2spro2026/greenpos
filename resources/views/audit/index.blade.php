@extends('layouts.app')

@section('title', 'Événements d\'audit')
@section('breadcrumb', 'Administration / Audit')
@section('heading', 'Événements')
@section('subtitle', 'Recherchez, filtrez et exportez l\'historique des activités.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('audit.export')
            <a href="{{ route('audit.export', request()->query()) }}" class="gp-btn-secondary">Excel / CSV</a>
            <a href="{{ route('audit.export-pdf', request()->query()) }}" target="_blank" class="gp-btn-secondary">PDF</a>
        @endcan
        @can('audit.print')
            <a href="{{ route('audit.print', request()->query()) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('audit.purge')
            <a href="{{ route('audit.purge') }}" class="gp-btn-secondary text-rose-600">Purger</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('audit._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('audit.index') }}" class="mb-6 space-y-3 rounded-2xl border border-gp-border bg-white p-4 dark:border-white/10 dark:bg-white/5">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Recherche</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Description, IP, module…" class="gp-input w-full">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Utilisateur</label>
                <select name="user_id" class="gp-select w-44">
                    <option value="">Tous</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Module</label>
                <select name="module" class="gp-select w-40">
                    <option value="">Tous</option>
                    @foreach($modules as $k => $v)
                        <option value="{{ $k }}" {{ ($filters['module'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                    <option value="auth" {{ ($filters['module'] ?? '') === 'auth' ? 'selected' : '' }}>Auth</option>
                    <option value="system" {{ ($filters['module'] ?? '') === 'system' ? 'selected' : '' }}>Système</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Action</label>
                <select name="action" class="gp-select w-44">
                    <option value="">Toutes</option>
                    @foreach($actions as $k => $v)
                        <option value="{{ $k }}" {{ ($filters['action'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Criticité</label>
                <select name="severity" class="gp-select w-40">
                    <option value="">Toutes</option>
                    @foreach($severities as $k => $v)
                        @if($k !== 'critical' || auth()->user()->can('audit.critical'))
                            <option value="{{ $k }}" {{ ($filters['severity'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Type</label>
                <select name="event_type" class="gp-select w-40">
                    <option value="">Tous</option>
                    @foreach($eventTypes as $k => $v)
                        <option value="{{ $k }}" {{ ($filters['event_type'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Boutique</label>
                <select name="store_id" class="gp-select w-40">
                    <option value="">Toutes</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ ($filters['store_id'] ?? '') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($companies->count() > 1)
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Entreprise</label>
                <select name="company_id" class="gp-select w-44">
                    <option value="">Active</option>
                    @foreach($companies as $co)
                        <option value="{{ $co->id }}" {{ ($filters['company_id'] ?? '') == $co->id ? 'selected' : '' }}>{{ $co->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Du</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="gp-input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Au</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="gp-input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">IP</label>
                <input type="text" name="ip" value="{{ $filters['ip'] ?? '' }}" placeholder="127.0.0.1" class="gp-input w-36">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gp-muted">Résultat</label>
                <select name="result" class="gp-select w-32">
                    <option value="">Tous</option>
                    @foreach($results as $k => $v)
                        <option value="{{ $k }}" {{ ($filters['result'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <button class="gp-btn-primary">Filtrer</button>
            @if(collect($filters)->filter()->isNotEmpty())
                <a href="{{ route('audit.index') }}" class="text-sm text-gp-muted hover:text-gp-text">Effacer</a>
            @endif
        </div>
    </form>

    <section class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3">Heure</th>
                        <th class="px-3 py-3">Utilisateur</th>
                        <th class="px-3 py-3">Entreprise</th>
                        <th class="px-3 py-3">Boutique</th>
                        <th class="px-3 py-3">Module</th>
                        <th class="px-3 py-3">Action</th>
                        <th class="px-3 py-3">Élément</th>
                        <th class="px-3 py-3">IP</th>
                        <th class="px-3 py-3">Appareil</th>
                        <th class="px-3 py-3">Résultat</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($events as $e)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                            <td class="whitespace-nowrap px-3 py-3 text-gp-muted">{{ $e->occurred_at->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-3 py-3 font-mono text-xs">{{ $e->occurred_at->format('H:i:s') }}</td>
                            <td class="px-3 py-3 font-semibold">{{ $e->user?->displayName() ?? 'Système' }}</td>
                            <td class="px-3 py-3 text-gp-muted">{{ $e->company?->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-gp-muted">{{ $e->store?->name ?? '—' }}</td>
                            <td class="px-3 py-3"><span class="rounded-md bg-gp-bg px-2 py-0.5 text-xs font-semibold dark:bg-white/5">{{ $e->module }}</span></td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $e->severityColor() }}">{{ $e->actionLabel() }}</span>
                                </span>
                            </td>
                            <td class="max-w-[160px] truncate px-3 py-3 text-gp-muted" title="{{ $e->subject_label }}">{{ $e->subject_label ?: '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 font-mono text-xs text-gp-muted">{{ $e->ip_address ?: '—' }}</td>
                            <td class="px-3 py-3 text-xs text-gp-muted">{{ $e->device ?: '—' }}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $e->result === 'success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200' : ($e->result === 'denied' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">{{ $e->resultLabel() }}</span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('audit.show', $e) }}" class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-white/10" title="Détail">
                                    <svg class="inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="px-6 py-16 text-center text-sm text-gp-muted">Aucun événement trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($events->hasPages())
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $events->links() }}</div>
        @endif
    </section>
@endsection
