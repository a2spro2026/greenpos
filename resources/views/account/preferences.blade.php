@extends('layouts.app')
@section('title', 'Préférences')
@section('breadcrumb', 'Mon compte')
@section('heading', 'Préférences')
@section('subtitle', 'Personnalisez votre expérience GreenPOS.')
@section('actions')
    <a href="{{ route('account.index') }}" class="gp-btn-secondary">Mon compte</a>
@endsection
@section('content')
<section class="grid gap-4 lg:grid-cols-2">
    <article class="gp-card">
        <h2 class="text-sm font-bold">Densité des tableaux</h2>
        <p class="mt-1 text-sm text-gp-muted">Appliquée immédiatement sur l’interface.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" class="gp-btn-secondary gp-density-btn" data-density="compact">Compacte</button>
            <button type="button" class="gp-btn-secondary gp-density-btn" data-density="comfortable">Confortable</button>
            <button type="button" class="gp-btn-secondary gp-density-btn" data-density="spacious">Aérée</button>
        </div>
    </article>
    <article class="gp-card">
        <h2 class="text-sm font-bold">Thème</h2>
        <p class="mt-1 text-sm text-gp-muted">Clair, sombre ou automatique (système).</p>
        <p class="mt-4 text-sm text-gp-muted">Utilisez le bouton thème dans l’en-tête pour basculer.</p>
        @can('settings.view')
            <a href="{{ route('settings.index') }}" class="gp-btn-primary mt-4 inline-flex">Paramètres entreprise</a>
        @endcan
        @can('notifications.preferences')
            <a href="{{ route('notifications.preferences') }}" class="gp-btn-secondary mt-4 inline-flex">Notifications</a>
        @endcan
    </article>
    <article class="gp-card lg:col-span-2">
        <h2 class="text-sm font-bold">Session</h2>
        <ul class="mt-3 space-y-2 text-sm text-gp-muted">
            <li>· Expiration automatique après <strong class="text-gp-text">{{ $sessionLifetime }} minutes</strong> d’inactivité.</li>
            <li>· Verrouillage manuel disponible depuis le menu utilisateur.</li>
            <li>· Un jeton CSRF invalide provoque une déconnexion sécurisée.</li>
        </ul>
        <form method="POST" action="{{ route('session.lock.store') }}" class="mt-4">@csrf<button class="gp-btn-secondary">Verrouiller la session maintenant</button></form>
    </article>
</section>
@endsection
