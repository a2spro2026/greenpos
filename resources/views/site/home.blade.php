@extends('layouts.site')

@section('title', 'Pilotez votre entreprise')
@section('meta_description', 'GreenPOS — plateforme SaaS tout-en-un pour gérer commerce, stock, ventes, équipes et croissance.')

@section('content')
<section class="site-hero" aria-label="Hero">
    <div class="site-hero-bg" aria-hidden="true"></div>
    <div class="site-hero-inner">
        <div>
            <p class="site-hero-brand"><span>GreenPOS</span></p>
            <h1>Pilotez votre entreprise avec GreenPOS.</h1>
            <p class="site-hero-lead">Une plateforme SaaS tout-en-un pour gérer votre commerce, votre stock, vos ventes, vos équipes et votre croissance.</p>
            <div class="site-hero-cta">
                <a href="{{ route('register-company') }}" class="site-btn site-btn-lime">Essai gratuit</a>
                <a href="{{ route('register-company') }}" class="site-btn site-btn-primary" style="background:#fff;color:var(--site-ink)">Créer mon entreprise</a>
                <a href="{{ route('site.contact', ['demo' => 1]) }}" class="site-btn site-btn-ghost" style="border-color:rgba(255,255,255,.28);color:#fff">Voir une démonstration</a>
            </div>
        </div>
        <div class="site-hero-visual" aria-hidden="true">
            <div class="site-ui-mock">
                <div class="site-ui-top"><i></i><i></i><i></i></div>
                <div class="site-ui-grid">
                    <div class="site-ui-panel">
                        <strong>Ventes du jour</strong>
                        <div class="site-ui-bars">
                            <span></span><span></span><span></span><span></span>
                        </div>
                    </div>
                    <div class="site-ui-panel">
                        <strong>Pilotage</strong>
                        <div class="site-ui-kpis">
                            <div class="site-ui-kpi"><b>POS</b><small>Caisse ouverte</small></div>
                            <div class="site-ui-kpi"><b>Stock</b><small>Alertes live</small></div>
                            <div class="site-ui-kpi"><b>CRM</b><small>Pipeline</small></div>
                            <div class="site-ui-kpi"><b>Multi</b><small>Boutiques</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-section" id="presentation">
    <div class="site-container site-split site-reveal" data-reveal>
        <div>
            <p class="site-eyebrow">Présentation</p>
            <h2>GreenPOS, le système nerveux de votre commerce.</h2>
            <p class="site-section-lead">De la caisse au stock, des achats à la facturation : une seule plateforme cloud pour centraliser vos opérations et accélérer vos décisions.</p>
        </div>
        <div class="site-why-list">
            <div class="site-why-item">
                <h3>Simple à démarrer</h3>
                <p>Créez votre entreprise, choisissez un plan, et votre espace est prêt après validation.</p>
            </div>
            <div class="site-why-item">
                <h3>Puissant au quotidien</h3>
                <p>POS fluide, stocks fiables, ventes suivies, équipes organisées — sans outils dispersés.</p>
            </div>
            <div class="site-why-item">
                <h3>Pensé pour évoluer</h3>
                <p>Passez d’une boutique à un réseau multi-sites sans changer de plateforme.</p>
            </div>
        </div>
    </div>
</section>

<section class="site-section site-section-tight" id="pourquoi">
    <div class="site-container site-reveal" data-reveal>
        <p class="site-eyebrow">Pourquoi GreenPOS</p>
        <h2>Pourquoi choisir GreenPOS</h2>
        <p class="site-section-lead">Une expérience premium, claire et orientée résultats — pour les équipes terrain comme pour la direction.</p>
        <div class="site-feature-rail">
            @foreach($reasons as $reason)
                <article class="site-feature-item">
                    <h3>{{ $reason['title'] }}</h3>
                    <p>{{ $reason['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="site-section" id="fonctionnalites">
    <div class="site-container site-reveal" data-reveal>
        <p class="site-eyebrow">Fonctionnalités</p>
        <h2>Fonctionnalités principales</h2>
        <p class="site-section-lead">Les briques essentielles pour vendre, approvisionner, facturer et piloter.</p>
        <div class="site-feature-rail">
            @foreach(array_slice($modules, 0, 8) as $module)
                <article class="site-feature-item">
                    <h3>{{ $module['title'] }}</h3>
                    <p>{{ $module['desc'] }}</p>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1.5rem">
            <a href="{{ route('site.features') }}" class="site-btn site-btn-ghost">Toutes les fonctionnalités</a>
        </div>
    </div>
</section>

<section class="site-section site-section-tight" id="secteurs">
    <div class="site-container site-reveal" data-reveal>
        <p class="site-eyebrow">Secteurs</p>
        <h2>Tous les secteurs supportés</h2>
        <p class="site-section-lead">GreenPOS s’adapte à votre métier, du commerce de proximité aux réseaux multi-sites.</p>
        <div class="site-sector-cloud">
            @foreach(array_slice($sectors, 0, 18) as $sector)
                <span class="site-sector-chip">{{ $sector }}</span>
            @endforeach
        </div>
        <div style="margin-top:1.5rem">
            <a href="{{ route('site.sectors') }}" class="site-btn site-btn-ghost">Voir tous les secteurs</a>
        </div>
    </div>
</section>

<section class="site-section" id="modules">
    <div class="site-container site-reveal" data-reveal>
        <p class="site-eyebrow">Modules</p>
        <h2>Modules disponibles</h2>
        <p class="site-section-lead">Activez ce dont vous avez besoin selon votre plan et votre organisation.</p>
        <div class="site-module-grid">
            @foreach($modules as $module)
                <div class="site-module-pill">{{ $module['title'] }}</div>
            @endforeach
        </div>
    </div>
</section>

<section class="site-section site-section-tight" id="temoignages">
    <div class="site-container site-reveal" data-reveal>
        <p class="site-eyebrow">Témoignages</p>
        <h2>Ils pilotent avec GreenPOS</h2>
        <div class="site-quote-grid" style="margin-top:2rem">
            @foreach($testimonials as $t)
                <blockquote class="site-quote">
                    <p>« {{ $t['quote'] }} »</p>
                    <footer>
                        <strong>{{ $t['name'] }}</strong>
                        {{ $t['role'] }}
                    </footer>
                </blockquote>
            @endforeach
        </div>
    </div>
</section>

<section class="site-section" id="tarifs">
    <div class="site-container site-reveal" data-reveal>
        <p class="site-eyebrow">Tarifs</p>
        <h2>Des plans clairs pour chaque étape</h2>
        <p class="site-section-lead">Démarrez simplement, puis évoluez avec votre réseau de boutiques.</p>
        @include('site.partials.pricing-cards', ['plans' => $plans])
        <div style="margin-top:1.5rem">
            <a href="{{ route('site.pricing') }}" class="site-btn site-btn-ghost">Comparer les offres</a>
        </div>
    </div>
</section>

<section class="site-section site-section-tight" id="faq">
    <div class="site-container site-reveal" data-reveal>
        <p class="site-eyebrow">FAQ</p>
        <h2>Questions fréquentes</h2>
        <div class="site-faq" style="margin-top:1.5rem">
            @foreach($faqs as $faq)
                <details>
                    <summary>{{ $faq['q'] }}</summary>
                    <p>{{ $faq['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

<section class="site-container">
    <div class="site-cta-band site-reveal" data-reveal>
        <h2>Prêt à piloter votre entreprise ?</h2>
        <p>Créez votre espace GreenPOS en quelques minutes. Notre équipe valide votre demande et active votre plateforme.</p>
        <div class="site-cta-actions">
            <a href="{{ route('register-company') }}" class="site-btn site-btn-lime">Créer mon entreprise</a>
            <a href="{{ route('site.contact', ['demo' => 1]) }}" class="site-btn site-btn-ghost" style="border-color:rgba(255,255,255,.3);color:#fff">Voir une démonstration</a>
        </div>
    </div>
</section>
@endsection
