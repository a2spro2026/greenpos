@extends('onboarding.layout')
@section('title', 'Créer mon compte')
@section('content')
<section class="ob-form-shell">
    <div class="ob-form-card">
        <p class="ob-eyebrow">Étape 1 / 3</p>
        <h1>Créer votre espace GreenPOS</h1>
        <p class="ob-lead-sm">Quelques informations pour démarrer. Vous choisirez votre plan ensuite.</p>

        @if($errors->any())
            <div class="ob-alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('onboarding.register.store') }}" class="ob-form">
            @csrf
            <label>Nom complet
                <input type="text" name="full_name" value="{{ old('full_name') }}" required autofocus>
            </label>
            <label>Nom de l’entreprise
                <input type="text" name="company_name" value="{{ old('company_name') }}" required>
            </label>
            <label>E-mail professionnel
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            </label>
            <label>Téléphone
                <input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel">
            </label>
            <div class="ob-form-row">
                <label>Mot de passe
                    <input type="password" name="password" required minlength="8" autocomplete="new-password">
                </label>
                <label>Confirmation
                    <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
                </label>
            </div>
            <label class="ob-check">
                <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                <span>J’accepte les conditions d’utilisation et la politique de confidentialité.</span>
            </label>
            <button type="submit" class="ob-btn-primary ob-btn-block">Continuer</button>
        </form>
        <p class="ob-form-footer">Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
    </div>
</section>
@endsection
