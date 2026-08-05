@extends('layouts.site')

@section('title', 'Secteurs d’activité')
@section('meta_description', 'GreenPOS pour épiceries, restaurants, pharmacies, garages, hôtels et bien d’autres secteurs.')

@section('content')
<section class="site-page-hero">
    <div class="site-container">
        <p class="site-eyebrow">Secteurs</p>
        <h1>Conçu pour votre activité.</h1>
        <p>Quel que soit votre métier, GreenPOS centralise caisse, stock, clients et pilotage dans une expérience unique.</p>
    </div>
</section>

<section class="site-section site-section-tight">
    <div class="site-container">
        @foreach($groups as $group => $items)
            <div class="site-group site-reveal" data-reveal>
                <h2>{{ $group }}</h2>
                <div class="site-sector-cloud">
                    @foreach($items as $sector)
                        <span class="site-sector-chip">{{ $sector }}</span>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="site-group site-reveal" data-reveal>
            <h2>Et aussi</h2>
            <div class="site-sector-cloud">
                @foreach($sectors as $sector)
                    <span class="site-sector-chip">{{ $sector }}</span>
                @endforeach
            </div>
        </div>

        <div class="site-cta-actions" style="justify-content:flex-start;margin-top:1rem">
            <a href="{{ route('register-company') }}" class="site-btn site-btn-primary">Créer mon entreprise</a>
            <a href="{{ route('site.contact') }}" class="site-btn site-btn-ghost">Parler à un expert</a>
        </div>
    </div>
</section>
@endsection
