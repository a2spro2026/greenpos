@extends('layouts.app')

@section('title', 'Importer des produits')
@section('breadcrumb', 'Catalogue / Produits')
@section('heading', 'Import CSV')
@section('subtitle', 'Colonnes : sku;name;type;barcode;unit;sale_price;purchase_price;tax_rate;status')

@section('content')
    <section class="gp-card max-w-xl space-y-4">
        <p class="text-sm text-gp-muted">Utilisez un fichier CSV séparé par des points-virgules. Les lignes existantes sont mises à jour via le SKU.</p>
        <form method="post" action="{{ route('products.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" required class="block w-full text-sm">
            @error('file')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
            <div class="flex gap-2">
                <a href="{{ route('products.index') }}" class="gp-btn-secondary">Retour</a>
                <button class="gp-btn-primary">Lancer l’import</button>
            </div>
        </form>
    </section>
@endsection
