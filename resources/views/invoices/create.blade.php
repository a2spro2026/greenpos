@extends('layouts.app')

@section('title', 'Nouvelle facture')
@section('breadcrumb', 'Finance / Facturation')
@section('heading', 'Créer une facture')
@section('subtitle', 'Formulaire premium avec calcul automatique.')

@section('content')
    @include('invoices._nav')
    @include('invoices._form')
@endsection
