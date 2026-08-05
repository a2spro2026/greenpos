@extends('layouts.superadmin')
@section('title', 'Changer de plan')
@section('breadcrumb', 'Billing / Abonnements')
@section('heading', 'Changement de plan — '.$subscription->tenant?->name)
@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <span class="sa-badge {{ $subscription->statusColor() }}">{{ $subscription->statusLabel() }}</span>
    <span class="sa-badge bg-slate-500/15 text-slate-300">Actuel : {{ $subscription->plan?->name }}</span>
</div>
<div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
@foreach($plans as $plan)
    @php
        $isCurrent = $plan->id === $subscription->saas_plan_id;
        $isUpgrade = $plan->sort_order > ($subscription->plan?->sort_order ?? 0);
        $isDowngrade = $plan->sort_order < ($subscription->plan?->sort_order ?? 0);
    @endphp
    <article class="sa-card flex flex-col {{ $isCurrent ? 'ring-1 ring-sky-400/40' : '' }}">
        <p class="text-[10px] font-bold uppercase tracking-widest text-sky-400/80">{{ strtoupper($plan->code) }}</p>
        <h2 class="text-lg font-bold text-white">{{ $plan->name }}</h2>
        <p class="mt-2 text-2xl font-bold text-sky-300">{{ number_format($plan->price_monthly, 0, ',', ' ') }} <span class="text-sm text-slate-500">MAD/mois</span></p>
        <ul class="mt-3 flex-1 space-y-1 text-xs text-slate-400">
            <li>{{ $plan->max_users }} users · {{ $plan->max_stores }} boutiques</li>
            <li>{{ $plan->storage_gb }} Go · Essai {{ $plan->trial_days }}j</li>
            <li>API {{ $plan->api_enabled ? '✓' : '✗' }} · Backup {{ $plan->backups_enabled ? '✓' : '✗' }}</li>
        </ul>
        @if($isCurrent)
            <button class="sa-btn sa-btn-ghost mt-4 w-full" disabled>Plan actuel</button>
        @elseif($isUpgrade)
            <form method="POST" action="{{ route('superadmin.subscriptions.upgrade', $subscription) }}" class="mt-4 space-y-2">
                @csrf
                <input type="hidden" name="saas_plan_id" value="{{ $plan->id }}">
                <select name="billing_cycle" class="sa-select text-xs">
                    <option value="monthly" {{ $subscription->billing_cycle === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                    <option value="yearly" {{ $subscription->billing_cycle === 'yearly' ? 'selected' : '' }}>Annuel</option>
                </select>
                <button class="sa-btn sa-btn-primary w-full">Monter en gamme</button>
            </form>
        @elseif($isDowngrade)
            <form method="POST" action="{{ route('superadmin.subscriptions.downgrade', $subscription) }}" class="mt-4 space-y-2" onsubmit="return confirm('Descendre de gamme ?')">
                @csrf
                <input type="hidden" name="saas_plan_id" value="{{ $plan->id }}">
                <select name="billing_cycle" class="sa-select text-xs">
                    <option value="monthly" {{ $subscription->billing_cycle === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                    <option value="yearly" {{ $subscription->billing_cycle === 'yearly' ? 'selected' : '' }}>Annuel</option>
                </select>
                <button class="sa-btn sa-btn-ghost w-full">Descendre de gamme</button>
            </form>
        @endif
    </article>
@endforeach
</div>
@endsection
