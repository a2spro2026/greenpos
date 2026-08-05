@extends('layouts.app')

@section('title', 'Nouveau devis')
@section('breadcrumb', 'Ventes / Devis')
@section('heading', 'Créer un devis')
@section('subtitle', 'Proposition commerciale avec calcul automatique.')

@section('content')
    @include('quotes._nav')
    @include('quotes._form')
@endsection
