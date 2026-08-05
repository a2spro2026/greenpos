@extends('layouts.admin')
@section('title', 'Plans')
@section('breadcrumb', 'Commercial')
@section('heading', 'Plans')
@section('content')
<div class="pa-grid-2">
@foreach($plans as $plan)
<div class="pa-card">
<div class="flex items-start justify-between gap-3">
<div>
<h2 class="text-base font-bold text-white">{{ $plan->name }}</h2>
<p class="text-xs text-zinc-500 pa-mono">{{ $plan->code }}</p>
</div>
<span class="pa-badge {{ $plan->is_active ? 'pa-badge-ok' : 'pa-badge-muted' }}">{{ $plan->is_active ? 'Actif' : 'Off' }}</span>
</div>
<p class="mt-3 text-2xl font-bold">{{ number_format((float)$plan->price_monthly,0,',',' ') }} <span class="text-sm font-medium text-zinc-500">{{ $plan->currency }}/mois</span></p>
<p class="mt-2 text-xs text-zinc-400">{{ count($plan->modules ?? []) }} modules · {{ $plan->max_users }} users · {{ $plan->max_stores }} boutiques</p>
</div>
@endforeach
</div>
@endsection
