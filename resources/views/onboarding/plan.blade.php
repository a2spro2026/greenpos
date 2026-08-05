@extends('onboarding.layout')
@section('title', 'Choisir un plan')
@section('content')
<section class="ob-form-shell ob-form-wide">
    <div class="ob-form-card ob-form-card-wide">
        <p class="ob-eyebrow">Étape 2 / 3 · {{ $draft['company_name'] ?? 'Votre entreprise' }}</p>
        <h1>Choisissez votre plan</h1>
        <p class="ob-lead-sm">Démarrez en essai gratuit 14 jours, ou activez un abonnement mensuel.</p>

        @if($errors->any())
            <div class="ob-alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('onboarding.plan.store') }}" id="ob-plan-form">
            @csrf
            <input type="hidden" name="saas_plan_id" id="ob-plan-id" value="{{ old('saas_plan_id', $plans->firstWhere('code', 'starter')?->id ?? $plans->first()?->id) }}">
            <input type="hidden" name="billing_mode" id="ob-billing-mode" value="trial">

            <div class="ob-pricing ob-pricing-select">
                @foreach($plans as $plan)
                    <button type="button" class="ob-price-card {{ ($plan->code === 'starter') ? 'selected' : '' }}" data-plan-id="{{ $plan->id }}" data-plan-select>
                        <h3>{{ $plan->name }}</h3>
                        <p class="ob-price"><strong>{{ number_format((float) $plan->price_monthly, 0, ',', ' ') }}</strong> <span>{{ $plan->currency }}/mois</span></p>
                        <ul>
                            <li>{{ $plan->max_users }} utilisateurs</li>
                            <li>{{ $plan->max_stores }} boutique(s)</li>
                            <li>{{ $plan->trial_days ?: 14 }} j. d’essai</li>
                        </ul>
                    </button>
                @endforeach
            </div>

            <div class="ob-plan-actions">
                <button type="submit" class="ob-btn-primary ob-btn-lg" onclick="document.getElementById('ob-billing-mode').value='trial'">Démarrer l’essai gratuit</button>
                <button type="submit" class="ob-btn-ghost ob-btn-lg" onclick="document.getElementById('ob-billing-mode').value='subscribe'">Choisir l’abonnement</button>
            </div>
        </form>
    </div>
</section>
<script>
document.querySelectorAll('[data-plan-select]').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('[data-plan-select]').forEach((b) => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('ob-plan-id').value = btn.getAttribute('data-plan-id');
    });
});
</script>
@endsection
