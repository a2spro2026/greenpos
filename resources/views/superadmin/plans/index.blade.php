@extends('layouts.superadmin')
@section('title', 'Plans')
@section('breadcrumb', 'Billing / Plans')
@section('heading', 'Plans d\'abonnement')
@section('actions')
    <a href="{{ route('superadmin.plans.create') }}" class="sa-btn sa-btn-primary">Nouveau plan</a>
@endsection
@section('content')
<div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
@foreach($plans as $plan)
    <article class="sa-card flex flex-col transition hover:-translate-y-0.5 hover:border-sky-500/30">
        <div class="mb-3 flex items-start justify-between gap-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-sky-400/80">{{ strtoupper($plan->code) }}</p>
                <h2 class="text-lg font-bold text-white">{{ $plan->name }}</h2>
                <p class="text-xs text-slate-500">{{ $plan->tagline }}</p>
            </div>
            @if(! $plan->is_active)<span class="sa-badge bg-rose-500/15 text-rose-300">Off</span>@endif
        </div>
        <p class="text-2xl font-bold text-sky-300">{{ number_format($plan->price_monthly, 0, ',', ' ') }} <span class="text-sm text-slate-500">MAD/mois</span></p>
        <p class="mt-1 text-xs text-slate-500">{{ number_format($plan->price_yearly, 0, ',', ' ') }} MAD/an · essai {{ $plan->trial_days ?? 14 }}j</p>
        <ul class="mt-4 flex-1 space-y-1.5 text-xs text-slate-400">
            <li>{{ $plan->max_users }} utilisateurs · {{ $plan->max_stores }} boutiques</li>
            <li>{{ $plan->storage_gb }} Go stockage</li>
            <li>API {{ $plan->api_enabled ? '✓' : '✗' }} · Domaine {{ $plan->custom_domain_enabled ? '✓' : '✗' }}</li>
            <li>Sauvegardes {{ $plan->backups_enabled ? '✓' : '✗' }} · Support {{ $plan->supportLabel() }}</li>
            <li>{{ count($plan->modules ?? []) }} modules inclus</li>
        </ul>
        <a href="{{ route('superadmin.plans.edit', $plan) }}" class="sa-btn sa-btn-ghost mt-4 w-full">Configurer</a>
    </article>
@endforeach
</div>
@endsection
