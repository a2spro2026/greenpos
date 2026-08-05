@extends('layouts.app')

@section('title', 'Nouvel inventaire')
@section('breadcrumb', 'Catalogue / Stock / Inventaires')
@section('heading', 'Nouvel inventaire')
@section('subtitle', 'Générez les lignes à compter pour une boutique.')

@section('actions')
    <a href="{{ route('stock.inventories.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('stock._nav')

    <form method="post" action="{{ route('stock.inventories.store') }}" class="mx-auto max-w-xl space-y-4">
        @csrf
        <section class="gp-card space-y-4">
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Nom</span>
                <input type="text" name="name" value="{{ old('name', 'Inventaire '.now()->format('d/m/Y')) }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Boutique</span>
                <select name="store_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) old('store_id', $workspaceStore?->id) === (string) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Notes</span>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes') }}</textarea>
            </label>
        </section>
        <button class="gp-btn-primary">Démarrer l’inventaire</button>
    </form>
@endsection
