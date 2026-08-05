@if(!empty($onboardingChecklist))
<section class="ob-checklist mb-6 overflow-hidden rounded-2xl border border-teal-500/20 bg-gradient-to-br from-teal-500/10 via-gp-surface to-gp-surface shadow-sm">
    @if(!empty($onboardingChecklist['show_welcome']) || session('welcome_onboarding'))
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gp-border/60 px-5 py-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-300">Bienvenue sur GreenPOS</p>
                <p class="mt-1 text-sm text-gp-text">Votre espace est prêt. Suivez la checklist pour encaisser votre première vente.</p>
            </div>
            <form method="POST" action="{{ route('onboarding.welcome.dismiss') }}">@csrf<button class="gp-btn-ghost text-xs">Masquer</button></form>
        </div>
    @endif
    <div class="px-5 py-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-bold">Démarrage rapide</h2>
            <span class="text-xs font-semibold text-gp-muted">{{ $onboardingChecklist['done'] }}/{{ $onboardingChecklist['total'] }} · {{ $onboardingChecklist['progress'] }}%</span>
        </div>
        <div class="mb-4 h-2 overflow-hidden rounded-full bg-gp-bg">
            <div class="h-full rounded-full bg-teal-600 transition-all" style="width: {{ $onboardingChecklist['progress'] }}%"></div>
        </div>
        <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($onboardingChecklist['items'] as $key => $item)
                <li>
                    <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-xl border border-gp-border bg-gp-surface/80 px-3 py-2.5 text-sm transition hover:border-teal-500/40">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md {{ $item['done'] ? 'bg-emerald-500 text-white' : 'border border-gp-border text-gp-muted' }}">
                            @if($item['done'])✓@else○@endif
                        </span>
                        <span class="{{ $item['done'] ? 'text-gp-muted line-through' : 'font-semibold' }}">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@elseif(session('welcome_onboarding'))
<section class="mb-6 rounded-2xl border border-teal-500/20 bg-teal-500/10 px-5 py-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-bold text-teal-800 dark:text-teal-200">Bienvenue sur GreenPOS 🎉</p>
            <p class="text-sm text-gp-muted">Votre entreprise est opérationnelle. Explorez le tableau de bord.</p>
        </div>
        <form method="POST" action="{{ route('onboarding.welcome.dismiss') }}">@csrf<button class="gp-btn-secondary text-xs">Compris</button></form>
    </div>
</section>
@endif
