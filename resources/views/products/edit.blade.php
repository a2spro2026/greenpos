@extends('layouts.app')

@section('title', 'Modifier '.$product->name)
@section('breadcrumb', 'Catalogue / Produits')
@section('heading', 'Modifier le produit')
@section('subtitle', $product->sku)

@section('content')
    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            <p class="font-semibold">Veuillez corriger les erreurs du formulaire.</p>
        </div>
    @endif

    <form method="post" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-6" data-gp-save>
        @csrf
        @method('PUT')
        @include('products._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('products.show', $product) }}" class="gp-btn-secondary">Annuler</a>
            <button class="gp-btn-primary">Mettre à jour</button>
        </div>
    </form>
@endsection
