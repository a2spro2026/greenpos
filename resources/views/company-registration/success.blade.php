@extends('layouts.site')

@section('title', 'Demande envoyée')
@section('meta_description', 'Votre demande d’inscription GreenPOS a été envoyée.')

@section('content')
<section class="site-section">
    <div class="site-container" style="max-width:34rem;text-align:center;padding-top:2rem">
        <div class="site-alert" style="text-align:left">
            <strong style="display:block;font-size:1.15rem;margin-bottom:.4rem">Votre demande a été envoyée avec succès.</strong>
            Elle sera étudiée par notre équipe.<br>
            Vous recevrez un email dès qu’elle sera validée.
        </div>
        @if($reference)
            <div class="reg-ref" role="group" aria-label="Numéro de demande">
                <div class="reg-ref-pointer" aria-hidden="true">
                    <span>Votre numéro</span>
                    <svg viewBox="0 0 72 44" fill="none">
                        <path d="M8 8c18 2 38 6 46 22" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                        <path d="M46 20l10 12-14 1" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <p class="reg-ref-code">{{ $reference }}</p>
            </div>
            <p style="color:var(--site-muted);margin-bottom:1.5rem">Conservez ce numéro pour suivre votre demande.</p>
            <div class="site-cta-actions" style="justify-content:center">
                <a href="{{ route('register-company.track.show', $reference) }}" class="site-btn site-btn-primary">Suivre ma demande</a>
                <a href="{{ route('site.home') }}" class="site-btn site-btn-ghost">Retour au site</a>
            </div>
        @else
            <div class="site-cta-actions" style="justify-content:center">
                <a href="{{ route('register-company.track') }}" class="site-btn site-btn-primary">Suivre ma demande</a>
                <a href="{{ route('site.home') }}" class="site-btn site-btn-ghost">Retour au site</a>
            </div>
        @endif
    </div>
</section>
@endsection
