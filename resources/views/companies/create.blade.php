@extends('layouts.app')

@section('title', 'Nouvelle entreprise')
@section('breadcrumb', 'Entreprises / Créer')
@section('heading', 'Nouvelle entreprise')
@section('subtitle', 'Créez une organisation indépendante. Vous en serez le propriétaire.')

@section('content')
    @include('companies._nav')
    <form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('companies._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('companies.index') }}" class="gp-btn-secondary">Annuler</a>
            <button type="submit" class="gp-btn-primary">Créer l'entreprise</button>
        </div>
    </form>
@endsection
