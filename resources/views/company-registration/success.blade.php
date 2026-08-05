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
            <p style="font-family:ui-monospace,monospace;font-weight:750;color:var(--site-green);font-size:1.05rem;letter-spacing:.03em">
                {{ $reference }}
            </p>
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
