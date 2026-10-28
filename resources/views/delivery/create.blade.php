@extends('layouts.app')

@section('title', 'Nouveau livreur')
@section('breadcrumb', 'Livraison')
@section('heading', 'Nouveau livreur')
@section('subtitle', 'Agent interne ou plateforme externe.')

@section('actions')
    <a href="{{ route('delivery.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('delivery._nav')
    @include('delivery._form')
@endsection
