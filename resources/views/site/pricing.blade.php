@extends('layouts.site')

@section('title', 'Tarifs')
@section('meta_description', 'Tarifs GreenPOS : Starter, Business et Enterprise. Choisissez le plan adapté à votre commerce.')

@section('content')
<section class="site-page-hero">
    <div class="site-container">
        <p class="site-eyebrow">Tarifs</p>
        <h1>Des offres simples, sans surprise.</h1>
        <p>Chaque plan inclut les modules adaptés à votre taille, le nombre de boutiques et d’utilisateurs, et un parcours d’activation professionnel.</p>
    </div>
</section>

<section class="site-section site-section-tight">
    <div class="site-container site-reveal" data-reveal>
        @include('site.partials.pricing-cards', ['plans' => $plans])
        <p style="margin-top:1.75rem;color:var(--site-muted);max-width:40rem;line-height:1.55">
            Après inscription, votre demande est étudiée par l’équipe GreenPOS. Une fois validée, votre entreprise, votre boutique et votre compte administrateur sont créés automatiquement.
        </p>
    </div>
</section>
@endsection
