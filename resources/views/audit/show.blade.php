@extends('layouts.app')

@section('title', 'Détail événement')
@section('breadcrumb', 'Administration / Audit')
@section('heading', 'Fiche événement')
@section('subtitle', $event->description ?: $event->actionLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('audit.index') }}" class="gp-btn-secondary">Retour</a>
        @can('audit.print')
            <a href="{{ route('audit.print-one', $event) }}" target="_blank" class="gp-btn-primary">Imprimer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('audit._nav')

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="rounded-full px-3 py-1 text-xs font-bold uppercase {{ $event->severityColor() }}">{{ $event->severityLabel() }}</span>
        <span class="rounded-full bg-gp-bg px-3 py-1 text-xs font-semibold dark:bg-white/5">{{ $event->actionLabel() }}</span>
        <span class="rounded-full bg-gp-bg px-3 py-1 text-xs font-semibold dark:bg-white/5">{{ $event->eventTypeLabel() }}</span>
        <span class="rounded-full px-3 py-1 text-xs font-bold uppercase {{ $event->result === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $event->resultLabel() }}</span>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="space-y-4 xl:col-span-2">
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Contexte</h2>
                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Date et heure</dt>
                        <dd class="mt-1 font-semibold">{{ $event->occurred_at->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Utilisateur</dt>
                        <dd class="mt-1 font-semibold">{{ $event->user?->displayName() ?? 'Système' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Action réalisée</dt>
                        <dd class="mt-1">{{ $event->actionLabel() }} · {{ $event->module }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Élément concerné</dt>
                        <dd class="mt-1">{{ $event->subject_label ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Entreprise</dt>
                        <dd class="mt-1">{{ $event->company?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Boutique</dt>
                        <dd class="mt-1">{{ $event->store?->name ?? '—' }}</dd>
                    </div>
                    @if($sessionDuration)
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Durée de session</dt>
                        <dd class="mt-1">{{ $sessionDuration }}</dd>
                    </div>
                    @endif
                    @if($event->duration_ms)
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Durée requête</dt>
                        <dd class="mt-1">{{ $event->duration_ms }} ms</dd>
                    </div>
                    @endif
                </dl>
            </article>

            <div class="grid gap-4 sm:grid-cols-2">
                <article class="gp-card">
                    <h2 class="mb-3 text-sm font-bold">Ancienne valeur</h2>
                    @if($event->old_values)
                        <pre class="max-h-80 overflow-auto rounded-xl bg-gp-bg p-3 text-xs dark:bg-black/30">{{ json_encode($event->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @else
                        <p class="text-sm text-gp-muted">Aucune valeur précédente.</p>
                    @endif
                </article>
                <article class="gp-card">
                    <h2 class="mb-3 text-sm font-bold">Nouvelle valeur</h2>
                    @if($event->new_values)
                        <pre class="max-h-80 overflow-auto rounded-xl bg-gp-bg p-3 text-xs dark:bg-black/30">{{ json_encode($event->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @else
                        <p class="text-sm text-gp-muted">Aucune valeur enregistrée.</p>
                    @endif
                </article>
            </div>

            @if($event->system_notes)
                <article class="gp-card border-dashed">
                    <h2 class="mb-2 text-sm font-bold">Commentaires système</h2>
                    <p class="text-sm text-gp-muted">{{ $event->system_notes }}</p>
                </article>
            @endif
        </section>

        <aside class="space-y-4">
            <article class="gp-card">
                <h2 class="mb-4 text-sm font-bold">Environnement</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Adresse IP</dt>
                        <dd class="mt-1 font-mono">{{ $event->ip_address ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Navigateur</dt>
                        <dd class="mt-1">{{ $event->browser ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Système d'exploitation</dt>
                        <dd class="mt-1">{{ $event->platform ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Appareil</dt>
                        <dd class="mt-1">{{ $event->device ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">User-Agent</dt>
                        <dd class="mt-1 break-all text-xs text-gp-muted">{{ $event->user_agent ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gp-muted">Route</dt>
                        <dd class="mt-1 text-xs">{{ $event->http_method }} {{ $event->route_name ?: $event->url }}</dd>
                    </div>
                </dl>
            </article>
        </aside>
    </div>
@endsection
