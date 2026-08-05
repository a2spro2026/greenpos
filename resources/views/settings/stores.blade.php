@extends('layouts.app')

@section('title', 'Boutiques')
@section('breadcrumb', 'Paramètres / Boutiques')
@section('heading', 'Boutiques')
@section('subtitle', 'Points de vente rattachés à votre entreprise.')

@section('content')
    <div class="flex flex-col gap-6 lg:flex-row">
        @include('settings._nav')

        <div class="min-w-0 flex-1 space-y-6">
            <section class="space-y-3">
                @forelse($stores as $store)
                    <article class="gp-card">
                        <form method="POST" action="{{ route('settings.stores.update', $store) }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gp-border pb-3 dark:border-white/10">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold">{{ $store->name }}</h3>
                                    @if($store->is_default)
                                        <span class="rounded-md bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-600">Par défaut</span>
                                    @endif
                                    @if(! $store->is_active)
                                        <span class="rounded-md bg-slate-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-500">Inactive</span>
                                    @endif
                                </div>
                                @can('settings.update')
                                <div class="flex gap-2">
                                    <button type="submit" class="gp-btn-secondary text-xs">Mettre à jour</button>
                                </div>
                                @endcan
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label class="gp-label">Nom *</label>
                                    <input type="text" name="name" value="{{ $store->name }}" class="gp-input" required>
                                </div>
                                <div>
                                    <label class="gp-label">Code</label>
                                    <input type="text" name="code" value="{{ $store->code }}" class="gp-input">
                                </div>
                                <div>
                                    <label class="gp-label">Ville</label>
                                    <input type="text" name="city" value="{{ $store->city }}" class="gp-input">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="gp-label">Adresse</label>
                                    <input type="text" name="address" value="{{ $store->address }}" class="gp-input">
                                </div>
                                <div>
                                    <label class="gp-label">Téléphone</label>
                                    <input type="text" name="phone" value="{{ $store->phone }}" class="gp-input">
                                </div>
                                <div>
                                    <label class="gp-label">Email</label>
                                    <input type="email" name="email" value="{{ $store->email }}" class="gp-input">
                                </div>
                                <div class="flex items-end gap-4 pb-2">
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_active" value="1" class="rounded border-gp-border" @checked($store->is_active)>
                                        Active
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_default" value="1" class="rounded border-gp-border" @checked($store->is_default)>
                                        Par défaut
                                    </label>
                                </div>
                            </div>
                        </form>
                        @can('settings.update')
                        <form method="POST" action="{{ route('settings.stores.destroy', $store) }}" class="mt-3 border-t border-gp-border pt-3 dark:border-white/10" onsubmit="return confirm('Supprimer cette boutique ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Supprimer</button>
                        </form>
                        @endcan
                    </article>
                @empty
                    <div class="gp-card py-12 text-center text-sm text-gp-muted">Aucune boutique.</div>
                @endforelse
            </section>

            @can('settings.update')
            <article class="gp-card space-y-4">
                <h2 class="text-sm font-bold">Ajouter une boutique</h2>
                <form method="POST" action="{{ route('settings.stores.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @csrf
                    <div>
                        <label class="gp-label">Nom *</label>
                        <input type="text" name="name" class="gp-input" required>
                    </div>
                    <div>
                        <label class="gp-label">Code</label>
                        <input type="text" name="code" class="gp-input">
                    </div>
                    <div>
                        <label class="gp-label">Ville</label>
                        <input type="text" name="city" class="gp-input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="gp-label">Adresse</label>
                        <input type="text" name="address" class="gp-input">
                    </div>
                    <div>
                        <label class="gp-label">Téléphone</label>
                        <input type="text" name="phone" class="gp-input">
                    </div>
                    <div class="flex items-end gap-4 pb-2 sm:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-gp-border" checked> Active
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_default" value="1" class="rounded border-gp-border"> Par défaut
                        </label>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <button type="submit" class="gp-btn-primary">Créer la boutique</button>
                    </div>
                </form>
            </article>
            @endcan
        </div>
    </div>
@endsection
