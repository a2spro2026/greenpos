@extends('layouts.superadmin')
@section('title', 'Module Manager')
@section('breadcrumb', 'Platform')
@section('heading', 'Module Manager')
@section('subtitle', 'Catalogue global et modules par plan SaaS.')
@section('content')
<div class="mb-6 grid gap-3 lg:grid-cols-4">
    @foreach($plans as $plan)
        <article class="sa-card">
            <h3 class="font-bold text-white">{{ $plan->name }}</h3>
            <p class="mt-1 text-xs text-slate-400">{{ count($plan->modules ?? []) }} modules</p>
        </article>
    @endforeach
</div>

<div class="space-y-8">
    @foreach($plans as $plan)
        <section class="sa-card">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-white">{{ $plan->name }}</h2>
                    <p class="text-xs text-slate-400">{{ $plan->tagline }} · code {{ $plan->code }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('superadmin.modules.plan.update', $plan) }}">
                @csrf
                @method('PUT')
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($catalog as $key => $meta)
                        <label class="flex items-start gap-2 rounded-xl border border-white/5 bg-white/[0.02] px-3 py-2.5 text-sm">
                            <input type="checkbox" name="modules[]" value="{{ $key }}" class="mt-1"
                                {{ in_array($key, $plan->modules ?? [], true) || in_array($key, \App\Support\ModuleCatalog::ALWAYS_ON, true) ? 'checked' : '' }}
                                {{ in_array($key, \App\Support\ModuleCatalog::ALWAYS_ON, true) ? 'disabled' : '' }}>
                            <span>
                                <span class="block font-semibold text-slate-100">{{ $meta['name'] }}</span>
                                <span class="block text-[11px] text-slate-500">{{ $meta['category'] }}</span>
                            </span>
                        </label>
                        @if(in_array($key, \App\Support\ModuleCatalog::ALWAYS_ON, true))
                            <input type="hidden" name="modules[]" value="{{ $key }}">
                        @endif
                    @endforeach
                </div>
                <button class="sa-btn sa-btn-primary mt-4">Enregistrer {{ $plan->name }}</button>
            </form>
        </section>
    @endforeach
</div>
@endsection
