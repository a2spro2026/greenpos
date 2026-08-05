@extends('layouts.superadmin')
@section('title', 'Abonnement #'.$subscription->id)
@section('breadcrumb', 'Billing / Abonnements')
@section('heading', $subscription->tenant?->name.' — '.$subscription->plan?->name)
@section('actions')
    <a href="{{ route('superadmin.subscriptions.change-plan', $subscription) }}" class="sa-btn sa-btn-ghost">Changer de plan</a>
    <a href="{{ route('superadmin.subscriptions.edit', $subscription) }}" class="sa-btn sa-btn-ghost">Modifier</a>
    @if(in_array($subscription->status, ['active', 'trialing', 'past_due'], true))
        <form method="POST" action="{{ route('superadmin.subscriptions.renew', $subscription) }}">@csrf<button class="sa-btn sa-btn-primary">Renouveler</button></form>
    @endif
@endsection
@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <span class="sa-badge {{ $subscription->statusColor() }}">{{ $subscription->statusLabel() }}</span>
    @if($subscription->auto_renew)<span class="sa-badge bg-emerald-500/15 text-emerald-300">Auto-renew</span>@endif
    @if($subscription->isExpiringSoon())<span class="sa-badge bg-amber-500/15 text-amber-300">Expire bientôt</span>@endif
    @if($subscription->status === 'trialing')
        <span class="sa-badge bg-sky-500/15 text-sky-300">Essai · {{ $subscription->trialDaysRemaining() ?? 0 }} j restants</span>
    @endif
    <span class="sa-badge bg-slate-500/15 text-slate-300">{{ $subscription->provider }}</span>
</div>

<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    @if($subscription->status === 'trialing')
        <form method="POST" action="{{ route('superadmin.subscriptions.convert-trial', $subscription) }}">@csrf<button class="sa-btn sa-btn-primary w-full">Convertir l’essai</button></form>
    @endif
    @if($subscription->status === 'suspended')
        <form method="POST" action="{{ route('superadmin.subscriptions.reactivate', $subscription) }}">@csrf<button class="sa-btn sa-btn-primary w-full">Réactiver</button></form>
    @else
        <form method="POST" action="{{ route('superadmin.subscriptions.suspend', $subscription) }}" onsubmit="return confirm('Suspendre ?')">@csrf
            <input type="hidden" name="reason" value="Suspension manuelle">
            <button class="sa-btn sa-btn-danger w-full">Suspendre</button>
        </form>
    @endif
    <form method="POST" action="{{ route('superadmin.subscriptions.past-due', $subscription) }}">@csrf<button class="sa-btn sa-btn-ghost w-full">Marquer impayé</button></form>
    <form method="POST" action="{{ route('superadmin.subscriptions.cancel', $subscription) }}" onsubmit="return confirm('Résilier définitivement ?')">@csrf
        <input type="hidden" name="reason" value="Résiliation manuelle">
        <button class="sa-btn sa-btn-ghost w-full text-rose-300">Résilier</button>
    </form>
    <form method="POST" action="{{ route('superadmin.subscriptions.issue-invoice', $subscription) }}">@csrf<button class="sa-btn sa-btn-ghost w-full">Émettre facture</button></form>
    <a href="{{ route('superadmin.payments.create') }}" class="sa-btn sa-btn-ghost w-full">Enregistrer paiement</a>
</div>

<div class="grid gap-4 xl:grid-cols-3">
    <article class="sa-card space-y-3 text-sm xl:col-span-2">
        <h2 class="text-sm font-bold text-white">Détails</h2>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-slate-500">Montant</dt><dd class="text-lg font-bold text-sky-300">{{ number_format($subscription->amount, 2, ',', ' ') }} {{ $subscription->currency }}</dd></div>
            <div><dt class="text-xs text-slate-500">MRR équivalent</dt><dd>{{ number_format($subscription->monthlyEquivalent(), 2, ',', ' ') }} MAD</dd></div>
            <div><dt class="text-xs text-slate-500">Cycle</dt><dd>{{ $subscription->billing_cycle === 'yearly' ? 'Annuel' : 'Mensuel' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Renouvellements</dt><dd>{{ $subscription->renewal_count }}</dd></div>
            <div><dt class="text-xs text-slate-500">Début</dt><dd>{{ optional($subscription->starts_at)->format('d/m/Y H:i') }}</dd></div>
            <div><dt class="text-xs text-slate-500">Fin / Renew</dt><dd>{{ optional($subscription->ends_at)->format('d/m/Y') }} · {{ optional($subscription->renews_at)->format('d/m/Y') }}</dd></div>
            @if($subscription->status === 'trialing')
                <div><dt class="text-xs text-slate-500">Fin d’essai</dt><dd class="text-sky-300">{{ optional($subscription->trial_ends_at ?: $subscription->ends_at)->format('d/m/Y') }} ({{ $subscription->trialDaysRemaining() }} j)</dd></div>
            @endif
            @if($subscription->converted_at)<div><dt class="text-xs text-slate-500">Converti le</dt><dd>{{ $subscription->converted_at->format('d/m/Y H:i') }}</dd></div>@endif
            @if($subscription->suspend_reason)<div class="sm:col-span-2"><dt class="text-xs text-slate-500">Motif suspension</dt><dd class="text-amber-300">{{ $subscription->suspend_reason }}</dd></div>@endif
            @if($subscription->cancel_reason)<div class="sm:col-span-2"><dt class="text-xs text-slate-500">Motif résiliation</dt><dd class="text-rose-300">{{ $subscription->cancel_reason }}</dd></div>@endif
            @if($subscription->notes)<div class="sm:col-span-2"><dt class="text-xs text-slate-500">Notes</dt><dd>{{ $subscription->notes }}</dd></div>@endif
        </dl>

        <h3 class="pt-4 text-sm font-bold text-white">Droits du plan (entitlements)</h3>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 text-xs">
            <div class="rounded-lg border border-white/5 px-3 py-2">API : <strong>{{ !empty($entitlements['api']) ? 'Oui' : 'Non' }}</strong></div>
            <div class="rounded-lg border border-white/5 px-3 py-2">Domaine custom : <strong>{{ !empty($entitlements['custom_domain']) ? 'Oui' : 'Non' }}</strong></div>
            <div class="rounded-lg border border-white/5 px-3 py-2">Sauvegardes : <strong>{{ !empty($entitlements['backups']) ? 'Oui' : 'Non' }}</strong></div>
            <div class="rounded-lg border border-white/5 px-3 py-2">Support : <strong>{{ !empty($entitlements['support']) ? 'Oui' : 'Non' }}</strong></div>
            <div class="rounded-lg border border-white/5 px-3 py-2">Max users : <strong>{{ $entitlements['max_users'] ?? '—' }}</strong></div>
            <div class="rounded-lg border border-white/5 px-3 py-2">Max stores : <strong>{{ $entitlements['max_stores'] ?? '—' }}</strong></div>
        </div>
        @if(!empty($entitlements['modules']))
            <div class="flex flex-wrap gap-1.5 pt-2">
                @foreach($entitlements['modules'] as $m)
                    <span class="sa-badge bg-slate-500/20 text-slate-300">{{ $m }}</span>
                @endforeach
            </div>
        @endif

        @if(!($limits['ok'] ?? true))
            <div class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                <p class="font-semibold">Dépassement des limites</p>
                <p class="mt-1 text-xs">{{ implode(' · ', $limits['breaches'] ?? []) }}</p>
            </div>
        @endif

        <h3 class="pt-4 text-sm font-bold text-white">Historique paiements</h3>
        <div class="overflow-x-auto">
            <table class="sa-table">
                <thead><tr><th>Réf</th><th>Provider</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($subscription->payments as $p)
                    <tr>
                        <td class="sa-mono text-xs">{{ $p->number }}</td>
                        <td>{{ $p->providerLabel() }}</td>
                        <td>{{ number_format($p->amount, 2, ',', ' ') }}</td>
                        <td>{{ $p->statusLabel() }}</td>
                        <td class="text-slate-400">{{ optional($p->paid_at)->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">Aucun paiement</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <aside class="space-y-4">
        <article class="sa-card">
            <h2 class="mb-3 text-sm font-bold text-white">Plan {{ $subscription->plan?->name }}</h2>
            <ul class="space-y-2 text-xs text-slate-400">
                <li>{{ $subscription->plan?->max_users }} utilisateurs</li>
                <li>{{ $subscription->plan?->max_stores }} boutiques</li>
                <li>{{ $subscription->plan?->storage_gb }} Go</li>
                <li>Essai : {{ $subscription->plan?->trial_days }} jours</li>
                <li>Support : {{ $subscription->plan?->supportLabel() }}</li>
            </ul>
        </article>
        <article class="sa-card">
            <h2 class="mb-3 text-sm font-bold text-white">Alertes</h2>
            @forelse($subscription->alerts as $a)
                <div class="mb-3 border-b border-white/5 pb-3 last:border-0">
                    <span class="sa-badge {{ $a->severityColor() }}">{{ $a->typeLabel() }}</span>
                    <p class="mt-1 text-sm text-white">{{ $a->title }}</p>
                    <p class="text-xs text-slate-500">{{ $a->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-xs text-slate-500">Aucune alerte</p>
            @endforelse
        </article>
        <article class="sa-card">
            <h2 class="mb-3 text-sm font-bold text-white">Licences</h2>
            @foreach($subscription->licenses as $lic)
                <p class="sa-mono text-xs text-sky-200">{{ $lic->license_key }}</p>
                <p class="mb-2 text-xs text-slate-500">{{ $lic->statusLabel() }}</p>
            @endforeach
        </article>
    </aside>
</div>
@endsection
