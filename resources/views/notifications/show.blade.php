@extends('layouts.app')

@section('title', $notification->title)
@section('breadcrumb', 'Notifications / Détail')
@section('heading', $notification->title)
@section('subtitle', $notification->categoryLabel().' · '.$notification->created_at->format('d/m/Y H:i'))

@section('actions')
    <div class="flex flex-wrap gap-2">
        @if($notification->action_url)
            <a href="{{ $notification->action_url }}" class="gp-btn-secondary">Ouvrir le module</a>
        @endif
        @can('notifications.archive')
            @if($notification->status !== 'archived')
                <form method="POST" action="{{ route('notifications.archive', $notification) }}">@csrf<button class="gp-btn-secondary">Archiver</button></form>
            @endif
        @endcan
        @can('notifications.delete')
            <form method="POST" action="{{ route('notifications.destroy', $notification) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="gp-btn-primary bg-rose-600 hover:bg-rose-700">Supprimer</button></form>
        @endcan
    </div>
@endsection

@section('content')
    @include('notifications._nav')

    <article class="gp-card max-w-3xl space-y-5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $notification->typeColor() }}">{{ $notification->typeLabel() }}</span>
            <span class="text-xs text-gp-muted">Priorité {{ $notification->priorityLabel() }}</span>
            <span class="text-xs text-gp-muted">· {{ $notification->statusLabel() }}</span>
        </div>
        <p class="text-sm leading-relaxed text-gp-text dark:text-white">{{ $notification->body }}</p>
        <dl class="grid gap-3 sm:grid-cols-2 text-sm border-t border-gp-border pt-4 dark:border-white/10">
            <div><dt class="text-xs text-gp-muted">Destinataire</dt><dd class="font-semibold">{{ $notification->user?->name ?? 'Tous (entreprise)' }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Auteur</dt><dd class="font-semibold">{{ $notification->actor?->name ?? 'Système' }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Boutique</dt><dd class="font-semibold">{{ $notification->store?->name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Lue le</dt><dd class="font-semibold">{{ optional($notification->read_at)->format('d/m/Y H:i') ?: '—' }}</dd></div>
        </dl>
        @if($notification->channels)
            <div>
                <p class="mb-2 text-xs font-bold uppercase text-gp-muted">Canaux</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($notification->channels as $ch => $on)
                        @if($on)
                            <span class="rounded-lg bg-gp-bg px-2.5 py-1 text-xs font-semibold capitalize dark:bg-white/5">{{ $ch }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </article>
@endsection
