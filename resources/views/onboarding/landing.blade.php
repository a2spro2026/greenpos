@extends('onboarding.layout')
@section('title', 'Point de vente cloud')
@section('content')
<section class="ob-hero">
    <div class="ob-hero-copy">
        <p class="ob-eyebrow">GreenPOS Enterprise</p>
        <h1>Votre commerce, opérationnel en moins de 2 minutes.</h1>
        <p class="ob-lead">Caisse, stock, facturation, CRM et rapports — un espace SaaS prêt à l’emploi, sans installation.</p>
        <div class="ob-hero-cta">
            <a href="{{ route('onboarding.register') }}" class="ob-btn-primary ob-btn-lg">Essayer gratuitement</a>
            <a href="#pricing" class="ob-btn-ghost ob-btn-lg">Voir les tarifs</a>
        </div>
        <p class="ob-hero-note">14 jours d’essai · Aucune carte requise · Annulation à tout moment</p>
    </div>
    <div class="ob-hero-visual" aria-hidden="true">
        <div class="ob-mock">
            <div class="ob-mock-bar"><span></span><span></span><span></span></div>
            <div class="ob-mock-grid">
                <div class="ob-mock-card"><strong>CA du jour</strong><em>12 480 MAD</em></div>
                <div class="ob-mock-card"><strong>Tickets</strong><em>86</em></div>
                <div class="ob-mock-card tall"><strong>Pipeline</strong><div class="ob-mock-bars"><i></i><i></i><i></i><i></i></div></div>
            </div>
        </div>
    </div>
</section>

<section class="ob-section" id="features">
    <h2>Tout ce qu’il faut pour vendre</h2>
    <p class="ob-section-lead">Une suite unifiée, pensée pour le retail et la distribution au Maroc et au-delà.</p>
    <div class="ob-features">
        @foreach([
            ['POS temps réel', 'Encaissements rapides, multi-caisse, tickets et sessions.'],
            ['Stock & achats', 'Niveaux, alertes, réceptions et valorisation.'],
            ['Facturation', 'Devis, factures, paiements et suivi client.'],
            ['CRM Enterprise', 'Leads, pipeline et activités commerciales.'],
            ['GreenPOS AI', 'Assistance contextuelle sur vos modules métier.'],
            ['Multi-boutiques', 'Entreprises, magasins et rôles isolés.'],
        ] as [$t, $d])
            <article class="ob-feature">
                <h3>{{ $t }}</h3>
                <p>{{ $d }}</p>
            </article>
        @endforeach
    </div>
</section>

<section class="ob-section ob-section-alt" id="pricing">
    <h2>Des tarifs simples</h2>
    <p class="ob-section-lead">Commencez en essai gratuit, évoluez quand vous êtes prêt.</p>
    <div class="ob-pricing">
        @forelse($plans as $plan)
            <article class="ob-price-card {{ $plan->code === 'standard' ? 'featured' : '' }}">
                @if($plan->code === 'standard')
                    <span class="ob-pill">Populaire</span>
                @endif
                <h3>{{ $plan->name }}</h3>
                <p class="ob-price-tagline">{{ $plan->tagline ?: 'Pour votre équipe' }}</p>
                <p class="ob-price"><strong>{{ number_format((float) $plan->price_monthly, 0, ',', ' ') }}</strong> <span>{{ $plan->currency }}/mois</span></p>
                <ul>
                    <li>{{ $plan->max_users }} utilisateurs</li>
                    <li>{{ $plan->max_stores }} boutique(s)</li>
                    <li>{{ $plan->trial_days ?: 14 }} jours d’essai</li>
                    <li>{{ $plan->supportLabel() }}</li>
                </ul>
                <a href="{{ route('onboarding.register') }}" class="ob-btn-primary">Essayer {{ $plan->name }}</a>
            </article>
        @empty
            <p class="text-center text-gp-muted">Plans en cours de synchronisation…</p>
        @endforelse
    </div>
</section>

<section class="ob-section" id="testimonials">
    <h2>Ils nous font confiance</h2>
    <p class="ob-section-lead">Témoignages (aperçu — contenus à finaliser).</p>
    <div class="ob-quotes">
        <blockquote>
            <p>« On a ouvert notre deuxième boutique en une après-midi. »</p>
            <footer>— Retail Fashion, Casablanca <em>(à confirmer)</em></footer>
        </blockquote>
        <blockquote>
            <p>« Enfin un POS qui parle stock, factures et caisse dans la même interface. »</p>
            <footer>— Distribution Nord <em>(à confirmer)</em></footer>
        </blockquote>
        <blockquote>
            <p>« L’essai gratuit nous a convaincus en moins d’une heure. »</p>
            <footer>— Café & Convenience <em>(à confirmer)</em></footer>
        </blockquote>
    </div>
</section>

<section class="ob-section ob-section-alt" id="faq">
    <h2>FAQ</h2>
    <div class="ob-faq">
        <details open><summary>L’essai est-il vraiment gratuit ?</summary><p>Oui. 14 jours sans carte bancaire. Vous pouvez résilier à tout moment.</p></details>
        <details><summary>Combien de temps pour être opérationnel ?</summary><p>Inscription, choix du plan et assistant : moins de 2 minutes pour un espace fonctionnel.</p></details>
        <details><summary>Puis-je ajouter des boutiques plus tard ?</summary><p>Oui, selon les limites de votre plan (Starter à Enterprise).</p></details>
        <details><summary>Mes données sont-elles isolées ?</summary><p>Chaque entreprise est un espace isolé multi-tenant, avec rôles et permissions.</p></details>
    </div>
    <div class="ob-final-cta">
        <h3>Prêt à démarrer ?</h3>
        <a href="{{ route('onboarding.register') }}" class="ob-btn-primary ob-btn-lg">Essayer gratuitement</a>
    </div>
</section>
@endsection
