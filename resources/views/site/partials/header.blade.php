@php
    $nav = [
        ['route' => 'site.home', 'label' => 'Accueil'],
        ['route' => 'site.features', 'label' => 'Fonctionnalités'],
        ['route' => 'site.sectors', 'label' => 'Secteurs'],
        ['route' => 'site.pricing', 'label' => 'Tarifs'],
        ['route' => 'site.contact', 'label' => 'Contact'],
        ['route' => 'register-company.track', 'label' => 'Suivre ma demande', 'match' => 'register-company.track*'],
    ];
@endphp
<header class="site-header" data-site-header>
    <div class="site-container site-header-inner">
        <a href="{{ route('site.home') }}" class="site-brand" aria-label="GreenPOS — Accueil">
            <span class="site-brand-mark" aria-hidden="true"><span>GP</span></span>
            <span class="site-brand-name">GreenPOS</span>
        </a>

        <nav class="site-nav" aria-label="Navigation principale">
            @foreach($nav as $item)
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['match'] ?? $item['route']) ? 'is-active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="site-header-actions">
            <a href="{{ route('login') }}" class="site-btn site-btn-ghost">Connexion</a>
            <a href="{{ route('register-company') }}" class="site-btn site-btn-primary">Créer mon entreprise</a>
        </div>

        <button type="button" class="site-burger" data-site-burger aria-expanded="false" aria-controls="site-mobile-nav" aria-label="Ouvrir le menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>
    <div class="site-mobile-nav" id="site-mobile-nav" data-site-mobile>
        <div class="site-container">
            @foreach($nav as $item)
                <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
            @endforeach
            <div class="site-mobile-actions">
                <a href="{{ route('login') }}" class="site-btn site-btn-ghost">Connexion</a>
                <a href="{{ route('register-company') }}" class="site-btn site-btn-primary">Créer mon entreprise</a>
            </div>
        </div>
    </div>
</header>
