@extends('layouts.app')
@section('title', 'Options & variantes')
@section('heading', 'Options & variantes')
@section('subtitle', 'Extras avec supplément de prix, distincts des variantes SKU du produit.')
@section('actions')
    <a href="{{ route('products.options.create') }}" class="gp-btn-primary">Nouvelle option</a>
    <a href="{{ route('products.index') }}" class="gp-btn-secondary">Produits</a>
@endsection
@section('content')
@if(session('success'))<div class="mb-4 gp-flash gp-flash-success">{{ session('success') }}</div>@endif
<div class="grid gap-4">
    @forelse($options as $option)
        <article class="gp-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold">{{ $option->name }}</h2>
                    <p class="text-xs text-gp-muted">{{ $option->selectionLabel() }} @if($option->is_required) · obligatoire @endif</p>
                </div>
                <a href="{{ route('products.options.edit', $option) }}" class="gp-btn-secondary text-xs">Modifier</a>
            </div>
            <ul class="mt-3 space-y-1 text-sm">
                @foreach($option->variants as $variant)
                    <li class="flex justify-between"><span>{{ $variant->name }} @unless($variant->is_active)<span class="text-xs text-gp-muted">(inactive)</span>@endunless</span><span>+ {{ number_format($variant->extra_price, 2, ',', ' ') }}</span></li>
                @endforeach
            </ul>
        </article>
    @empty
        <p class="gp-card text-sm text-gp-muted">Aucune option. Les variantes SKU existantes ne changent pas.</p>
    @endforelse
</div>
@endsection
