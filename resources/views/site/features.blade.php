@extends('layouts.site')

@section('title', 'Fonctionnalités')
@section('meta_description', 'Découvrez les modules GreenPOS : POS, stock, achats, CRM, facturation, multi-boutiques et plus.')

@section('content')
<section class="site-page-hero">
    <div class="site-container">
        <p class="site-eyebrow">Fonctionnalités</p>
        <h1>Tous les modules pour faire tourner votre entreprise.</h1>
        <p>Une suite complète, activable selon votre plan — conçue pour la caisse, le back-office et la direction.</p>
    </div>
</section>

<section class="site-section site-section-tight">
    <div class="site-container site-reveal" data-reveal>
        <div class="site-feature-rail">
            @foreach($modules as $module)
                <article class="site-feature-item">
                    <h3>{{ $module['title'] }}</h3>
                    <p>{{ $module['desc'] }}</p>
                </article>
            @endforeach
        </div>
        <div style="margin-top:2.5rem" class="site-cta-actions">
            <a href="{{ route('register-company') }}" class="site-btn site-btn-primary">Créer mon entreprise</a>
            <a href="{{ route('site.pricing') }}" class="site-btn site-btn-ghost">Voir les tarifs</a>
        </div>
    </div>
</section>
@endsection
