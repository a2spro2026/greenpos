@php
    $featured = $plans->firstWhere('code', 'standard')?->id;
@endphp
<div class="site-price-grid">
    @foreach($plans as $plan)
        @php
            $features = collect($plan->features ?? [])->take(5);
        @endphp
        <article class="site-price-card {{ $plan->id === $featured ? 'is-featured' : '' }}">
            <div>
                <h3>{{ $plan->name }}</h3>
                <p style="margin:.35rem 0 0;color:var(--site-muted);font-size:.92rem">{{ $plan->tagline }}</p>
            </div>
            <div class="amount">
                {{ number_format((float) $plan->price_monthly, 0, ',', ' ') }}
                <small>{{ $plan->currency }}/mois</small>
            </div>
            <ul>
                <li>{{ $plan->max_stores }} boutique{{ $plan->max_stores > 1 ? 's' : '' }}</li>
                <li>{{ $plan->max_users }} utilisateur{{ $plan->max_users > 1 ? 's' : '' }}</li>
                @foreach($features as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
                <li>Modules inclus selon le plan {{ $plan->name }}</li>
            </ul>
            <a href="{{ route('register-company') }}" class="site-btn {{ $plan->id === $featured ? 'site-btn-accent' : 'site-btn-primary' }}">Commencer</a>
        </article>
    @endforeach
</div>
