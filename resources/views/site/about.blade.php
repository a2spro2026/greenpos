@extends('layouts.site')

@section('title', 'À propos')
@section('meta_description', 'À propos de GreenPOS — la plateforme SaaS qui aide les entreprises à piloter leurs opérations.')

@section('content')
<section class="site-page-hero">
    <div class="site-container">
        <p class="site-eyebrow">À propos</p>
        <h1>Une plateforme née pour le commerce réel.</h1>
        <p>GreenPOS accompagne les entreprises qui veulent vendre mieux, gérer plus simplement et grandir sans friction.</p>
    </div>
</section>

<section class="site-section site-section-tight">
    <div class="site-container site-reveal" data-reveal>
        <div class="site-about-story">
            <p>GreenPOS est une plateforme SaaS tout-en-un conçue pour les commerces, réseaux de boutiques et organisations de services. Elle réunit POS, stock, achats, clients, CRM, facturation et pilotage dans un seul espace sécurisé.</p>
            <p>Notre approche est simple : une expérience premium pour les équipes, une architecture solide pour la croissance, et une console plateforme pour accompagner chaque entreprise avec exigence.</p>
            <p>Que vous ouvriez votre première caisse ou que vous structuriez un réseau multi-sites, GreenPOS vous donne les outils pour avancer avec clarté.</p>
        </div>
        <div class="site-cta-actions" style="justify-content:flex-start;margin-top:2rem">
            <a href="{{ route('register-company') }}" class="site-btn site-btn-primary">Créer mon entreprise</a>
            <a href="{{ route('site.contact') }}" class="site-btn site-btn-ghost">Nous contacter</a>
        </div>
    </div>
</section>
@endsection
