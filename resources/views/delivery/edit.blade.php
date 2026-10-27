@extends('layouts.app')

@section('title', 'Modifier livreur')
@section('breadcrumb', 'Livraison')
@section('heading', 'Modifier '.$agent->name)
@section('subtitle', $agent->typeLabel())

@section('actions')
    <a href="{{ route('delivery.show', $agent) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('delivery._nav')
    @include('delivery._form')
@endsection
