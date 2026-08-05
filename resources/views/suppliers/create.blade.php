@extends('layouts.app')

@section('title', 'Nouveau fournisseur')
@section('breadcrumb', 'Approvisionnement / Fournisseurs')
@section('heading', 'Nouveau fournisseur')
@section('subtitle', 'Créez une fiche partenaire complète.')

@section('actions')
    <a href="{{ route('suppliers.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('suppliers._nav')
    @include('suppliers._form')
@endsection
