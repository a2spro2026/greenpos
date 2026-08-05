<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GreenPOS') — GreenPOS</title>
    @vite(['resources/css/app.css', 'resources/css/onboarding.css', 'resources/js/app.js'])
</head>
<body class="ob-body">
    <header class="ob-nav">
        <a href="{{ route('onboarding.landing') }}" class="ob-brand">
            <span class="ob-brand-mark">GP</span>
            <span>GreenPOS</span>
        </a>
        <nav class="ob-nav-links">
            <a href="{{ route('onboarding.landing') }}#features">Fonctionnalités</a>
            <a href="{{ route('onboarding.landing') }}#pricing">Tarifs</a>
            <a href="{{ route('onboarding.landing') }}#faq">FAQ</a>
            <a href="{{ route('login') }}" class="ob-link-muted">Connexion</a>
            <a href="{{ route('onboarding.register') }}" class="ob-btn-primary">Essayer gratuitement</a>
        </nav>
        <button type="button" class="ob-nav-toggle" id="ob-nav-toggle" aria-label="Menu">☰</button>
    </header>
    <div class="ob-nav-mobile" id="ob-nav-mobile" hidden>
        <a href="{{ route('onboarding.landing') }}#features">Fonctionnalités</a>
        <a href="{{ route('onboarding.landing') }}#pricing">Tarifs</a>
        <a href="{{ route('onboarding.landing') }}#faq">FAQ</a>
        <a href="{{ route('login') }}">Connexion</a>
        <a href="{{ route('onboarding.register') }}" class="ob-btn-primary">Essayer gratuitement</a>
    </div>
    @yield('content')
    <footer class="ob-footer">
        <p>© {{ date('Y') }} GreenPOS Enterprise · SaaS point de vente & gestion</p>
        <p><a href="{{ route('login') }}">Connexion</a> · <a href="{{ route('onboarding.register') }}">Créer un compte</a></p>
    </footer>
    <script>
        document.getElementById('ob-nav-toggle')?.addEventListener('click', () => {
            const m = document.getElementById('ob-nav-mobile');
            if (m) m.hidden = !m.hidden;
        });
    </script>
</body>
</html>
