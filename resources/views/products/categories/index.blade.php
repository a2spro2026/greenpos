@extends('layouts.app')
@section('title', 'Catégories')
@section('breadcrumb', 'Catalogue / Catégories')
@section('heading', 'Catégories')
@section('subtitle', 'Catégories affichées au terminal de caisse et dans le catalogue. L’ordre définit l’affichage.')
@section('actions')
    <a href="{{ route('products.subcategories.index') }}" class="gp-btn-secondary">Sous-catégories</a>
@endsection
@section('content')
@if(session('success'))<div class="mb-4 gp-flash gp-flash-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif
@can('create', App\Models\Product::class)
<form method="POST" action="{{ route('products.categories.store') }}" class="gp-card mb-4 grid gap-3 sm:grid-cols-4">
    @csrf
    <input name="name" placeholder="Nouvelle catégorie" class="gp-input sm:col-span-2" required>
    <input name="sort_order" type="number" min="0" placeholder="Ordre" class="gp-input">
    <button class="gp-btn-primary">Ajouter</button>
</form>
@endcan
<section class="gp-card space-y-3">
    @forelse($categories as $cat)
        <form method="POST" action="{{ route('products.categories.update', $cat) }}" class="grid items-center gap-2 border-b border-gp-border pb-3 sm:grid-cols-5 dark:border-white/10">
            @csrf @method('PUT')
            <input name="name" value="{{ $cat->name }}" class="gp-input sm:col-span-2">
            <input name="sort_order" type="number" min="0" value="{{ $cat->sort_order }}" class="gp-input" aria-label="Ordre">
            <a href="{{ route('products.index', ['category_id' => $cat->id]) }}" class="text-sm text-gp-primary hover:underline">{{ $counts[$cat->id] ?? 0 }} produit(s)</a>
            @can('create', App\Models\Product::class)<button class="gp-btn-secondary">Mettre à jour</button>@endcan
        </form>
    @empty
        <p class="text-sm text-gp-muted">Aucune catégorie. Ajoutez-en une ci-dessus : elle apparaîtra au terminal de caisse.</p>
    @endforelse
</section>
@endsection
