@extends('layouts.app')

@section('title', 'Documents')
@section('breadcrumb', 'Pilotage / Documents')
@section('heading', 'Gestion documentaire')
@section('subtitle', 'Centralisez, classez et partagez tous vos fichiers métier.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('documents.create')
            <a href="{{ route('documents.upload') }}" class="gp-btn-primary">Importer</a>
        @endcan
        <a href="{{ route('documents.index') }}" class="gp-btn-secondary">Explorateur</a>
    </div>
@endsection

@section('content')
    @include('documents._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Documents</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Taille utilisée</p><p class="mt-2 text-2xl font-bold">{{ $stats['size_human'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Favoris</p><p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['favorites'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Archivés</p><p class="mt-2 text-3xl font-bold text-slate-500">{{ $stats['archived'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Dossiers</p><p class="mt-2 text-3xl font-bold">{{ $stats['folders'] }}</p></article>
    </section>

    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <section class="xl:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-bold">Documents récents</h2>
            </div>
            <div class="space-y-2">
                @forelse($stats['recent'] as $doc)
                    <a href="{{ route('documents.show', $doc) }}" class="gp-card flex items-center gap-3 transition hover:-translate-y-0.5 hover:shadow-md !py-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl text-xs font-bold uppercase {{ $doc->iconColor() }}">{{ $doc->extension }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $doc->name }}</p>
                            <p class="text-xs text-gp-muted">{{ $doc->humanSize() }} · {{ $doc->uploader?->name ?? '—' }} · {{ $doc->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <div class="gp-card py-10 text-center text-sm text-gp-muted">Aucun document.</div>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-sm font-bold">Favoris</h2>
            <div class="space-y-2">
                @forelse($stats['favorites_list'] as $doc)
                    <a href="{{ route('documents.show', $doc) }}" class="gp-card block !py-3">
                        <p class="truncate font-semibold">{{ $doc->name }}</p>
                        <p class="text-xs text-gp-muted">{{ strtoupper($doc->extension) }} · {{ $doc->humanSize() }}</p>
                    </a>
                @empty
                    <div class="gp-card py-8 text-center text-sm text-gp-muted">Aucun favori.</div>
                @endforelse
            </div>

            <h2 class="mb-3 mt-6 text-sm font-bold">Par module</h2>
            <div class="gp-card space-y-2">
                @forelse($stats['by_module'] as $mod => $cnt)
                    <div class="flex justify-between text-sm">
                        <span>{{ \App\Models\Document::MODULES[$mod] ?? ($mod ?: '—') }}</span>
                        <span class="font-bold">{{ $cnt }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gp-muted">Pas encore de répartition.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
