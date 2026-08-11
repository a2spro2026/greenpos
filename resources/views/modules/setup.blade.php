<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Catalogue des modules — GreenPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('gp-theme');
                if (t === 'dark') document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/css/modules.css', 'resources/js/modules.js'])
</head>
<body class="ms-setup-body">
<header class="ms-setup-top">
    <div class="ms-setup-brand">
        <span class="ms-setup-mark">GP</span>
        <div>
            <strong>GreenPOS</strong>
            <span>{{ $company->name }}</span>
        </div>
    </div>
    <div class="ms-setup-top-meta">
        @if($plan)
            <span class="ms-setup-plan">{{ $plan->name }}</span>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="ms-setup-logout">Déconnexion</button>
        </form>
    </div>
</header>

<div class="ms-setup-shell" data-module-setup>
    <aside class="ms-setup-rail" aria-label="Catégories">
        <p class="ms-setup-rail-label">Catalogue</p>
        <nav class="ms-setup-nav">
            <button type="button" class="ms-setup-nav-item is-active" data-ms-chip="Tous">
                <span>Tous</span>
                <em>{{ $availableCount }}</em>
            </button>
            @foreach($sections as $section)
                <button type="button" class="ms-setup-nav-item" data-ms-chip="{{ $section['key'] }}" data-ms-scroll="#cat-{{ $section['slug'] }}">
                    <span>{{ $section['emoji'] }} {{ $section['key'] }}</span>
                    <em>{{ $section['available'] }}</em>
                </button>
            @endforeach
        </nav>
    </aside>

    <main class="ms-setup-main">
        <section class="ms-setup-hero">
            <p class="ms-setup-kicker">Première configuration</p>
            <h1>Composez votre ERP</h1>
            <p>Choisissez les applications dont votre entreprise a besoin. Après validation, tout ajout passera par le Super Admin GreenPOS.</p>
            <label class="ms-search ms-search--hero">
                <svg class="ms-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                <input type="search" placeholder="Rechercher un module..." data-ms-search autocomplete="off">
            </label>
        </section>

        <form method="POST" action="{{ route('modules.setup.store') }}" id="ms-setup-form">
            @csrf

            @foreach($sections as $section)
                <section class="ms-setup-section" id="cat-{{ $section['slug'] }}" data-ms-section="{{ $section['key'] }}">
                    <header class="ms-setup-section-head">
                        <h2>{{ $section['emoji'] }} {{ $section['key'] }}</h2>
                        <p>{{ $section['lead'] }}</p>
                    </header>
                    <div class="ms-setup-grid">
                        @foreach($section['modules'] as $mod)
                            @php
                                $selectable = $mod['in_plan'] && empty($mod['coming_soon']) && $mod['action'] !== 'upgrade' && $mod['action'] !== 'soon';
                            @endphp
                            <article
                                class="ms-app {{ $selectable ? 'is-pickable' : 'is-locked' }}"
                                data-ms-card
                                data-selectable="{{ $selectable ? '1' : '0' }}"
                                data-key="{{ $mod['key'] }}"
                                data-name="{{ strtolower($mod['name'].' '.$mod['description'].' '.$mod['category']) }}"
                                data-category="{{ $mod['category'] }}"
                                style="--ms-accent: {{ $mod['color'] ?? '#0d9488' }}"
                            >
                                @if($selectable)
                                    <input type="checkbox" name="modules[]" value="{{ $mod['key'] }}" class="ms-pick-input">
                                @endif
                                <span class="ms-app-icon" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $mod['icon_path'] }}"/></svg>
                                </span>
                                <div class="ms-app-body">
                                    <div class="ms-app-title-row">
                                        <h3>{{ $mod['name'] }}</h3>
                                        @if($selectable)
                                            <span class="ms-app-flag ms-pick-state">Disponible</span>
                                        @elseif(!empty($mod['coming_soon']))
                                            <span class="ms-app-flag is-soon">Bientôt</span>
                                        @else
                                            <span class="ms-app-flag is-premium">Premium</span>
                                        @endif
                                    </div>
                                    <p>{{ $mod['description'] }}</p>
                                    <span class="ms-app-dev">GreenPOS · v{{ $mod['version'] }}</span>
                                </div>
                                @if($selectable)
                                    <button type="button" class="ms-app-cta ms-pick-btn">Ajouter</button>
                                @else
                                    <span class="ms-app-cta is-disabled">Verrouillé</span>
                                @endif
                                <span class="ms-app-check" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <p class="ms-empty is-hidden" data-ms-empty-filter hidden>Aucun module ne correspond à votre recherche.</p>
        </form>
    </main>
</div>

<div class="ms-setup-bar">
    <div>
        <strong data-ms-count>0</strong>
        <span>module(s) sélectionné(s)</span>
    </div>
    <button type="submit" form="ms-setup-form" class="ms-setup-submit">Valider et entrer</button>
</div>
</body>
</html>
