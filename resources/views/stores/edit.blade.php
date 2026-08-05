@extends('layouts.app')

@section('title', 'Modifier boutique')
@section('breadcrumb', 'Boutiques / Modifier')
@section('heading', 'Modifier · '.$store->name)
@section('subtitle', 'Mettez à jour les informations et accès.')

@section('content')
    @include('stores._nav')
    <form method="POST" action="{{ route('stores.update', $store) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        @include('stores._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('stores.show', $store) }}" class="gp-btn-secondary">Annuler</a>
            <button type="submit" class="gp-btn-primary">Enregistrer</button>
        </div>
    </form>
@endsection
