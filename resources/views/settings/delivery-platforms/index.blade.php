@extends('layouts.app')
@section('title', 'Plateformes de livraison')
@section('heading', 'Plateformes de livraison')
@section('subtitle', 'Chaque plateforme active est synchronisée dans les modes de service.')
@section('content')
<div class="flex flex-col gap-6 lg:flex-row">
    @include('settings._nav', ['section' => 'platforms'])
    <div class="min-w-0 flex-1 space-y-4">
        @if(session('success'))<div class="gp-flash gp-flash-success">{{ session('success') }}</div>@endif
        <form method="POST" action="{{ route('settings.delivery-platforms.store') }}" class="gp-card grid gap-3 sm:grid-cols-6">
            @csrf
            <input name="name" placeholder="Nom" class="gp-input sm:col-span-2" required>
            <select name="kind" class="gp-select"><option value="external">Externe</option><option value="internal">Interne</option></select>
            <select name="commission_type" class="gp-select"><option value="percent">% commission</option><option value="fixed">Fixe</option></select>
            <input type="number" step="0.01" name="commission_value" value="0" class="gp-input">
            <button class="gp-btn-primary">Ajouter</button>
            <label class="inline-flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_delivery_agent" value="1" checked> Compte comme livreur</label>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
        </form>
        <section class="gp-card space-y-4">
            @forelse($platforms as $platform)
                <form method="POST" action="{{ route('settings.delivery-platforms.update', $platform) }}" class="grid gap-2 border-b border-gp-border pb-4 sm:grid-cols-6 dark:border-white/10">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $platform->name }}" class="gp-input sm:col-span-2">
                    <select name="kind" class="gp-select"><option value="external" @selected($platform->kind==='external')>Externe</option><option value="internal" @selected($platform->kind==='internal')>Interne</option></select>
                    <select name="commission_type" class="gp-select"><option value="percent" @selected($platform->commission_type==='percent')>%</option><option value="fixed" @selected($platform->commission_type==='fixed')>Fixe</option></select>
                    <input type="number" step="0.01" name="commission_value" value="{{ $platform->commission_value }}" class="gp-input">
                    <button class="gp-btn-secondary">Sync</button>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_delivery_agent" value="1" @checked($platform->is_delivery_agent)> Livreur</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($platform->is_active)> Active</label>
                    <span class="text-xs text-gp-muted sm:col-span-2">Code liste : platform-{{ $platform->id }}</span>
                </form>
            @empty
                <p class="text-sm text-gp-muted">Aucune plateforme. Les modes Sur place / Emporter / Livraison restent disponibles.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
