@extends('layouts.app')

@section('title', 'Notifications')
@section('breadcrumb', 'Pilotage / Notifications')
@section('heading', 'Centre de notifications')
@section('subtitle', 'Toutes vos alertes métier, centralisées et filtrables.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('notifications.update')
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">@csrf<button class="gp-btn-secondary">Tout marquer lu</button></form>
        @endcan
        @can('notifications.preferences')
            <a href="{{ route('notifications.preferences') }}" class="gp-btn-primary">Préférences</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('notifications._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Non lues</p>
            <p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['unread'] }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Critiques</p>
            <p class="mt-2 text-3xl font-bold text-rose-600">{{ $stats['critical'] }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Aujourd'hui</p>
            <p class="mt-2 text-3xl font-bold">{{ $stats['today'] }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Archivées</p>
            <p class="mt-2 text-3xl font-bold text-slate-500">{{ $stats['archived'] }}</p>
        </article>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="xl:col-span-2 space-y-3">
            <h2 class="text-sm font-bold">Timeline récente</h2>
            @forelse($recent as $n)
                <a href="{{ route('notifications.show', $n) }}" class="gp-card flex gap-4 transition hover:-translate-y-0.5 hover:shadow-md {{ $n->isUnread() ? 'ring-1 ring-gp-primary/30' : '' }}">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $n->typeColor() }} text-xs font-bold uppercase">{{ substr($n->type, 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold {{ $n->isUnread() ? '' : 'text-gp-muted' }}">{{ $n->title }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $n->typeColor() }}">{{ $n->typeLabel() }}</span>
                            @if($n->isUnread())<span class="h-2 w-2 rounded-full bg-gp-primary"></span>@endif
                        </div>
                        <p class="mt-1 line-clamp-2 text-sm text-gp-muted">{{ $n->body }}</p>
                        <p class="mt-2 text-xs text-gp-muted">{{ $n->categoryLabel() }} · {{ $n->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @empty
                <div class="gp-card py-12 text-center text-sm text-gp-muted">Aucune notification.</div>
            @endforelse
        </section>

        <section class="space-y-3">
            <h2 class="text-sm font-bold">Critiques</h2>
            @forelse($critical as $n)
                <article class="gp-card border-rose-200/60 dark:border-rose-500/20">
                    <p class="font-semibold text-rose-700 dark:text-rose-300">{{ $n->title }}</p>
                    <p class="mt-1 text-xs text-gp-muted">{{ $n->body }}</p>
                    <a href="{{ route('notifications.show', $n) }}" class="mt-3 inline-block text-xs font-semibold text-gp-primary hover:underline">Voir</a>
                </article>
            @empty
                <div class="gp-card py-8 text-center text-sm text-gp-muted">Aucune alerte critique.</div>
            @endforelse

            <article class="gp-card border-dashed">
                <p class="text-xs font-bold uppercase text-gp-muted">Canaux préparés</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach(['Email', 'SMS', 'WhatsApp', 'Push'] as $ch)
                        <span class="rounded-lg bg-gp-bg px-2.5 py-1 text-xs font-semibold dark:bg-white/5">{{ $ch }}</span>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-gp-muted">Connecteurs externes prêts à brancher.</p>
            </article>
        </section>
    </div>
@endsection
