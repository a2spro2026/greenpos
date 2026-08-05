<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion — {{ ($loginBranding['trade_name'] ?? null) ?: 'GreenPOS' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/auth.css'])
    <style>
        :root {
            --auth-primary: {{ $loginBranding['primary_color'] ?? '#0f766e' }};
            --auth-primary-hover: {{ $loginBranding['link_color'] ?? '#0d9488' }};
            --auth-accent: {{ $loginBranding['link_color'] ?? '#14b8a6' }};
        }
        @if(!empty($loginBrandingUrls['background']))
        .gp-auth-hero {
            background:
                linear-gradient(165deg, rgba(7, 21, 18, 0.82), rgba(11, 31, 28, 0.78)),
                url('{{ $loginBrandingUrls['background'] }}') center/cover no-repeat !important;
        }
        @endif
    </style>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('gp-theme');
                if (t === 'dark') document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
</head>
<body class="gp-auth-body">
@php
    $showOther = ($mode ?? 'default') === 'other' || request('mode') === 'other';
    $hasLast = !empty($lastAccount) && !$showOther;
    $brandName = $loginBranding['trade_name'] ?? 'GreenPOS';
    $btnColor = $loginBranding['button_color'] ?? null;
@endphp

<div class="gp-auth-shell">
    <aside class="gp-auth-hero" aria-hidden="false">
        <div class="gp-auth-hero-bg"></div>

        <div>
            <div class="gp-auth-brand">
                @if(!empty($loginBrandingUrls['logo']))
                    <img src="{{ $loginBrandingUrls['logo'] }}" alt="{{ $brandName }}" class="gp-auth-brand-logo">
                @else
                    <span class="gp-auth-brand-mark">GP</span>
                    <span class="gp-auth-brand-name">{{ $brandName }}</span>
                @endif
            </div>

            <div class="gp-auth-hero-copy">
                <h1>Le cœur numérique de votre entreprise.</h1>
                <p>Une plateforme moderne pour gérer vos ventes, votre stock, vos boutiques, vos équipes et votre croissance.</p>

                <div class="gp-auth-features">
                    @foreach([
                        'Multi-entreprises',
                        'Multi-boutiques',
                        'ERP modulaire',
                        'Tableau de bord intelligent',
                        'Sauvegardes automatiques',
                        'GreenPOS AI',
                    ] as $feat)
                        <div class="gp-auth-feature">
                            <span class="gp-auth-feature-check" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span>{{ $feat }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="gp-auth-illu" aria-hidden="true">
            <div class="gp-auth-illu-stage">
                <svg class="gp-auth-links-svg" viewBox="0 0 400 220" fill="none">
                    <path d="M120 80 C180 60, 220 90, 280 70" stroke="#2dd4bf" stroke-width="1.2" stroke-dasharray="4 4"/>
                    <path d="M140 150 C190 130, 230 160, 300 140" stroke="#5eead4" stroke-width="1.2" stroke-dasharray="4 4"/>
                    <path d="M100 110 C160 140, 240 100, 310 120" stroke="#99f6e4" stroke-width="1" stroke-dasharray="3 5"/>
                </svg>

                <div class="gp-auth-float gp-auth-float--dash">
                    <div class="gp-auth-dash-bar"></div>
                    <div class="gp-auth-dash-rows">
                        <span></span><span></span><span></span>
                    </div>
                </div>

                <div class="gp-auth-float gp-auth-float--pos">
                    <div class="gp-auth-pos-screen"></div>
                    <div class="gp-auth-pos-keys">
                        <i></i><i></i><i></i><i></i><i></i><i></i>
                    </div>
                </div>

                <div class="gp-auth-float gp-auth-float--chart">
                    <div class="gp-auth-chart-bars">
                        <b></b><b></b><b></b><b></b><b></b>
                    </div>
                </div>

                <div class="gp-auth-float gp-auth-float--mod">
                    <div class="gp-auth-mod-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="gp-auth-mod-line"></div>
                    <div class="gp-auth-mod-line"></div>
                </div>
            </div>
        </div>

        <p class="gp-auth-hero-foot">Déjà adopté par les entreprises qui veulent centraliser toute leur gestion.</p>
    </aside>

    <main class="gp-auth-panel">
        <a href="{{ route('site.home') }}" class="gp-auth-back">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Retour à l’accueil
        </a>

        <div class="gp-auth-card" @if($btnColor) style="--auth-primary: {{ $btnColor }};" @endif>
            <div class="gp-auth-card-head">
                @if(!empty($loginBrandingUrls['logo']))
                    <img src="{{ $loginBrandingUrls['logo'] }}" alt="" class="gp-auth-brand-logo">
                @else
                    <span class="gp-auth-brand-mark" style="width:2.35rem;height:2.35rem;font-size:0.7rem;">GP</span>
                @endif
                <span class="gp-auth-secure">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Connexion sécurisée
                </span>
                <h2>Bienvenue sur {{ $brandName }}</h2>
                <p>Accédez à votre espace pour piloter ventes, stock et équipes.</p>
            </div>

            @if(session('status'))
                <div class="gp-auth-info" role="status">
                    <svg class="gp-auth-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if(!empty($sessionExpired) || session('session_expired'))
                <div class="gp-auth-alert" role="alert">
                    <svg class="gp-auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/></svg>
                    <span>Votre session a expiré. Reconnectez-vous pour continuer en toute sécurité.</span>
                </div>
            @endif

            @if($errors->any())
                <div class="gp-auth-alert" role="alert">
                    <svg class="gp-auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if($authenticated && $currentUser && !$showOther)
                <div class="gp-auth-session">
                    <div class="gp-auth-avatar">
                        @if($currentUser->photoUrl())
                            <img src="{{ $currentUser->photoUrl() }}" alt="">
                        @else
                            {{ $currentUser->initials() }}
                        @endif
                    </div>
                    <div class="gp-auth-session-meta">
                        <strong>{{ $currentUser->displayName() }}</strong>
                        @if(!empty($currentCompanyName))
                            <span class="gp-auth-company">{{ $currentCompanyName }}</span>
                        @endif
                        <span class="gp-auth-email">{{ $currentUser->email }}</span>
                    </div>
                </div>
                <a href="{{ route('home') }}" class="gp-auth-btn">Continuer avec ce compte</a>
                <form method="POST" action="{{ route('login.switch') }}">
                    @csrf
                    <button type="submit" class="gp-auth-btn-ghost">Changer de compte</button>
                </form>

            @elseif($hasLast)
                <div class="gp-auth-session">
                    <div class="gp-auth-avatar">
                        @if(!empty($lastAccount['photo']))
                            <img src="{{ $lastAccount['photo'] }}" alt="">
                        @else
                            {{ $lastAccount['initials'] ?? 'U' }}
                        @endif
                    </div>
                    <div class="gp-auth-session-meta">
                        <strong>{{ $lastAccount['name'] ?? 'Compte' }}</strong>
                        @if(!empty($lastAccount['company']))
                            <span class="gp-auth-company">{{ $lastAccount['company'] }}</span>
                        @endif
                        <span class="gp-auth-email">{{ $lastAccount['email'] ?? '' }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('login.continue') }}">
                    @csrf
                    <div class="gp-auth-field">
                        <label for="password-continue">Mot de passe</label>
                        <div class="gp-auth-input-wrap">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <input id="password-continue" class="gp-auth-input" type="password" name="password" required autofocus autocomplete="current-password" placeholder="••••••••">
                        </div>
                    </div>
                    <label class="gp-auth-remember">
                        <input type="checkbox" name="remember" value="1">
                        Rester connecté
                    </label>
                    <button type="submit" class="gp-auth-btn">Continuer avec ce compte</button>
                </form>
                <form method="POST" action="{{ route('login.switch') }}">
                    @csrf
                    <button type="submit" class="gp-auth-btn-ghost">Changer de compte</button>
                </form>

            @else
                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <div class="gp-auth-field">
                        <label for="email">E-mail</label>
                        <div class="gp-auth-input-wrap">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input
                                id="email"
                                class="gp-auth-input"
                                type="email"
                                name="email"
                                value="{{ old('email', app()->environment('local', 'testing') ? 'admin@greenpos.test' : '') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="vous@entreprise.com"
                            >
                        </div>
                    </div>
                    <div class="gp-auth-field">
                        <label for="password">Mot de passe</label>
                        <div class="gp-auth-input-wrap">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <input id="password" class="gp-auth-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        </div>
                    </div>
                    <label class="gp-auth-remember">
                        <input type="checkbox" name="remember" value="1">
                        Rester connecté
                    </label>
                    <button type="submit" class="gp-auth-btn">Se connecter</button>
                </form>

                @if(app()->environment('local', 'testing'))
                    <p class="gp-auth-demo">Démo : admin@greenpos.test / password</p>
                @endif

                <p class="gp-auth-links">
                    Nouveau client ?
                    <a href="{{ route('register-company') }}">Créer mon entreprise</a>
                </p>
            @endif
        </div>

        <footer class="gp-auth-footer">
            <span class="gp-auth-footer-version">GreenPOS v1.0</span>
            <nav class="gp-auth-footer-nav" aria-label="Liens légaux">
                <a href="{{ route('site.contact') }}">Conditions</a>
                <a href="{{ route('site.contact') }}">Confidentialité</a>
                <a href="{{ route('site.contact') }}">Support</a>
            </nav>
        </footer>
    </main>
</div>
</body>
</html>
