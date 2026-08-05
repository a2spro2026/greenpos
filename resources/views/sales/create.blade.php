@extends('layouts.app')

@section('title', 'Nouvelle vente')
@section('breadcrumb', 'Ventes / Nouvelle vente')
@section('heading', 'Créer une vente')
@section('subtitle', 'Saisie manuelle d\'une vente.')

@section('content')
    @include('sales._nav')
    @include('sales._form')
@endsection
