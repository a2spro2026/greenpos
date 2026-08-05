@extends('layouts.site')

@section('title', 'Contact')
@section('meta_description', 'Contactez l’équipe GreenPOS pour une démonstration, un accompagnement ou une question commerciale.')

@section('content')
<section class="site-page-hero">
    <div class="site-container">
        <p class="site-eyebrow">Contact</p>
        <h1>{{ !empty($demo) ? 'Voir une démonstration' : 'Parlons de votre projet.' }}</h1>
        <p>{{ !empty($demo) ? 'Indiquez vos coordonnées : nous organisons une démonstration adaptée à votre activité.' : 'Une question, un besoin spécifique ou une demande de démo — notre équipe vous répond.' }}</p>
    </div>
</section>

<section class="site-section site-section-tight">
    <div class="site-container site-reveal" data-reveal>
        @if(session('success'))
            <div class="site-alert">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="site-alert site-alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('site.contact.submit') }}" class="site-form">
            @csrf
            <label class="site-label">Nom complet
                <input class="site-input" type="text" name="name" value="{{ old('name') }}" required>
            </label>
            <label class="site-label">Email
                <input class="site-input" type="email" name="email" value="{{ old('email') }}" required>
            </label>
            <label class="site-label">Entreprise
                <input class="site-input" type="text" name="company" value="{{ old('company') }}">
            </label>
            <label class="site-label">Téléphone
                <input class="site-input" type="tel" name="phone" value="{{ old('phone') }}">
            </label>
            <label class="site-label">Sujet
                <input class="site-input" type="text" name="subject" value="{{ old('subject', !empty($demo) ? 'Demande de démonstration' : '') }}">
            </label>
            <label class="site-label">Message
                <textarea class="site-textarea" name="message" rows="5" required>{{ old('message') }}</textarea>
            </label>
            <button type="submit" class="site-btn site-btn-primary">Envoyer</button>
        </form>
    </div>
</section>
@endsection
