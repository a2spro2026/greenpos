@extends('layouts.app')

@section('title', 'Modifier entreprise')
@section('breadcrumb', 'Entreprises / Modifier')
@section('heading', 'Modifier · '.$company->name)
@section('subtitle', 'Mettez à jour l\'identité et la localisation.')

@section('content')
    @include('companies._nav')
    <form method="POST" action="{{ route('companies.update', $company) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        @include('companies._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('companies.show', $company) }}" class="gp-btn-secondary">Annuler</a>
            <button type="submit" class="gp-btn-primary">Enregistrer</button>
        </div>
    </form>
@endsection
