@extends('layouts.app')

@section('title', 'Préférences notifications')
@section('breadcrumb', 'Notifications / Préférences')
@section('heading', 'Préférences')
@section('subtitle', 'Choisissez ce que vous recevez et par quels canaux.')

@section('content')
    @include('notifications._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('notifications.preferences.update') }}" class="space-y-6 max-w-4xl">
        @csrf
        @method('PUT')

        <article class="gp-card space-y-4">
            <div class="flex items-center justify-between gap-3 border-b border-gp-border pb-4 dark:border-white/10">
                <div>
                    <h2 class="text-sm font-bold">Réception</h2>
                    <p class="text-xs text-gp-muted">Activer ou suspendre les notifications</p>
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="enabled" value="1" class="rounded border-gp-border" @checked($preferences->enabled)>
                    Activées
                </label>
            </div>
            <div>
                <label class="gp-label">Fréquence</label>
                <select name="frequency" class="gp-input max-w-xs">
                    @foreach(['realtime' => 'Temps réel', 'hourly' => 'Horaire', 'daily' => 'Quotidienne', 'weekly' => 'Hebdomadaire'] as $k => $v)
                        <option value="{{ $k }}" @selected($preferences->frequency === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        </article>

        <article class="gp-card space-y-4">
            <h2 class="text-sm font-bold border-b border-gp-border pb-4 dark:border-white/10">Types</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($types as $key => $label)
                    <label class="inline-flex items-center gap-2 rounded-xl bg-gp-bg px-3 py-2.5 text-sm dark:bg-white/5">
                        <input type="checkbox" name="types[]" value="{{ $key }}" class="rounded border-gp-border" @checked(in_array($key, $preferences->types ?? [], true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </article>

        <article class="gp-card space-y-4">
            <h2 class="text-sm font-bold border-b border-gp-border pb-4 dark:border-white/10">Catégories métier</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach($categories as $key => $label)
                    <label class="inline-flex items-center gap-2 rounded-xl bg-gp-bg px-3 py-2.5 text-sm dark:bg-white/5">
                        <input type="checkbox" name="categories[]" value="{{ $key }}" class="rounded border-gp-border" @checked(in_array($key, $preferences->categories ?? [], true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </article>

        <article class="gp-card space-y-4">
            <h2 class="text-sm font-bold border-b border-gp-border pb-4 dark:border-white/10">Canaux</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    'internal' => 'Notifications internes',
                    'email' => 'Email',
                    'sms' => 'SMS (préparé)',
                    'whatsapp' => 'WhatsApp (préparé)',
                    'push' => 'Push mobile (préparé)',
                ] as $key => $label)
                    <label class="inline-flex items-center gap-2 rounded-xl bg-gp-bg px-3 py-2.5 text-sm dark:bg-white/5">
                        <input type="checkbox" name="channels[{{ $key }}]" value="1" class="rounded border-gp-border" @checked(data_get($preferences->channels, $key))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-gp-muted">SMS, WhatsApp et Push sont préparés — livraison réelle à brancher ultérieurement.</p>
        </article>

        <div class="flex justify-end">
            <button type="submit" class="gp-btn-primary">Enregistrer</button>
        </div>
    </form>
@endsection
