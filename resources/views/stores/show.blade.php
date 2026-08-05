@extends('layouts.app')

@section('title', $store->name)
@section('breadcrumb', 'Boutiques / Fiche')
@section('heading', $store->name)
@section('subtitle', ($store->city ?: '—').' · '.($store->code ?: 'sans code'))

@section('actions')
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('stores.switch', $store) }}">@csrf<button class="gp-btn-secondary">Activer cette boutique</button></form>
        @can('stores.print')
            <a href="{{ route('stores.print-one', $store) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('stores.update')
            <a href="{{ route('stores.edit', $store) }}" class="gp-btn-primary">Modifier</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('stores._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">CA</p><p class="mt-2 text-2xl font-bold">{{ number_format($store->metric_revenue ?? 0, 0, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Stock</p><p class="mt-2 text-2xl font-bold">{{ number_format($store->metric_stock ?? 0, 0, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Utilisateurs</p><p class="mt-2 text-2xl font-bold">{{ $store->users_count }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Produits</p><p class="mt-2 text-2xl font-bold">{{ $store->metric_products ?? $store->products_count }}</p></article>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <article class="gp-card xl:col-span-2 space-y-4">
            <div class="flex items-start gap-4">
                @if($store->logoUrl())
                    <img src="{{ $store->logoUrl() }}" class="h-20 w-20 rounded-2xl object-cover" alt="">
                @else
                    <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gp-primary-soft text-xl font-bold text-gp-primary">{{ $store->initials() }}</span>
                @endif
                <div>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $store->statusColor() }}">{{ $store->statusLabel() }}</span>
                    @if($store->is_default)<span class="ml-2 text-xs font-bold text-emerald-600">Par défaut</span>@endif
                    <p class="mt-2 text-sm text-gp-muted">{{ $store->address }}</p>
                    <p class="text-sm text-gp-muted">{{ collect([$store->postal_code, $store->city, $store->region, $store->country])->filter()->implode(', ') }}</p>
                </div>
            </div>
            <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                <div><dt class="text-xs text-gp-muted">Téléphone</dt><dd class="font-semibold">{{ $store->phone ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Email</dt><dd class="font-semibold">{{ $store->email ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Responsable</dt><dd class="font-semibold">{{ $store->manager?->name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gp-muted">Horaires</dt><dd class="font-semibold">{{ $store->openingHoursSummary() }}</dd></div>
            </dl>
        </article>

        <article class="gp-card space-y-3">
            <h2 class="text-sm font-bold">Actions</h2>
            @can('stores.update')
                @if($store->is_active)
                    <form method="POST" action="{{ route('stores.deactivate', $store) }}">@csrf<button class="gp-btn-secondary w-full justify-center">Désactiver</button></form>
                @else
                    <form method="POST" action="{{ route('stores.activate', $store) }}">@csrf<button class="gp-btn-secondary w-full justify-center">Réactiver</button></form>
                @endif
            @endcan
            @can('stores.delete')
                <form method="POST" action="{{ route('stores.destroy', $store) }}" onsubmit="return confirm('Supprimer cette boutique ?')">
                    @csrf @method('DELETE')
                    <button class="w-full rounded-xl px-4 py-2 text-sm font-semibold text-rose-600 ring-1 ring-rose-200 hover:bg-rose-50">Supprimer</button>
                </form>
            @endcan
            <div class="rounded-xl border border-dashed border-gp-border p-3 text-xs text-gp-muted dark:border-white/10">
                GPS : {{ $store->latitude ?: '—' }} / {{ $store->longitude ?: '—' }}
            </div>
        </article>
    </div>

    <article class="gp-card mt-6 overflow-hidden p-0">
        <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Utilisateurs autorisés</h2></div>
        @if($store->users->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gp-muted">Aucun utilisateur assigné.</p>
        @else
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @foreach($store->users as $user)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="font-semibold">{{ $user->name }}</span>
                        <span class="text-gp-muted">{{ $user->email }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </article>
@endsection
