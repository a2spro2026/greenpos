@extends('layouts.app')

@section('title', 'Nouvelle boutique')
@section('breadcrumb', 'Boutiques / Créer')
@section('heading', 'Nouvelle boutique')
@section('subtitle', 'Ajoutez un point de vente à votre réseau.')

@section('content')
    @include('stores._nav')
    <form method="POST" action="{{ route('stores.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('stores._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('stores.index') }}" class="gp-btn-secondary">Annuler</a>
            <button type="submit" class="gp-btn-primary">Créer</button>
        </div>
    </form>
@endsection
