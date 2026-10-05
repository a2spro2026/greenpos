@extends('layouts.app')
@section('title', 'Sous-catégories')
@section('heading', 'Sous-catégories')
@section('subtitle', 'Rattachées à une catégorie. La catégorie principale du produit est conservée.')
@section('content')
@if(session('success'))<div class="mb-4 gp-flash gp-flash-success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ route('products.subcategories.store') }}" class="gp-card mb-4 grid gap-3 sm:grid-cols-3">
    @csrf
    <select name="category_id" class="gp-select" required>
        <option value="">Catégorie</option>
        @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
    </select>
    <input name="name" placeholder="Sous-catégorie" class="gp-input" required>
    <button class="gp-btn-primary">Ajouter</button>
</form>
<section class="gp-card space-y-3">
    @forelse($subcategories as $sub)
        <form method="POST" action="{{ route('products.subcategories.update', $sub) }}" class="grid gap-2 border-b border-gp-border pb-3 sm:grid-cols-4 dark:border-white/10">
            @csrf @method('PUT')
            <span class="text-sm text-gp-muted">{{ $sub->category?->name }}</span>
            <input name="name" value="{{ $sub->name }}" class="gp-input">
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($sub->is_active)> Active</label>
            <button class="gp-btn-secondary">Mettre à jour</button>
        </form>
    @empty
        <p class="text-sm text-gp-muted">Aucune sous-catégorie.</p>
    @endforelse
</section>
@endsection
