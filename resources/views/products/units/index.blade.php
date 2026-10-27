@extends('layouts.app')
@section('title', 'Unités de mesure')
@section('heading', 'Unités de mesure')
@section('subtitle', 'Référentiel d\'unités. Le champ texte du produit reste le repli.')
@section('content')
@if(session('success'))<div class="mb-4 gp-flash gp-flash-success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ route('products.units.store') }}" class="gp-card mb-4 grid gap-3 sm:grid-cols-4">
    @csrf
    <input name="name" placeholder="Nom" class="gp-input" required>
    <input name="code" placeholder="Code (ex. pce)" class="gp-input" required>
    <input name="symbol" placeholder="Symbole" class="gp-input">
    <button class="gp-btn-primary">Ajouter</button>
</form>
<section class="gp-card space-y-3">
    @forelse($units as $unit)
        <form method="POST" action="{{ route('products.units.update', $unit) }}" class="grid gap-2 border-b border-gp-border pb-3 sm:grid-cols-5 dark:border-white/10">
            @csrf @method('PUT')
            <input name="name" value="{{ $unit->name }}" class="gp-input">
            <input name="code" value="{{ $unit->code }}" class="gp-input">
            <input name="symbol" value="{{ $unit->symbol }}" class="gp-input">
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($unit->is_active)> Active</label>
            <button class="gp-btn-secondary">Mettre à jour</button>
        </form>
    @empty
        <p class="text-sm text-gp-muted">Aucune unité. Les produits continuent d'utiliser pièce, kg, litre…</p>
    @endforelse
</section>
@endsection
