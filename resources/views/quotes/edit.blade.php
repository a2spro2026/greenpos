@extends('layouts.app')

@section('title', $quote->number)
@section('breadcrumb', 'Ventes / Devis')
@section('heading', 'Modifier '.$quote->number)
@section('subtitle', 'Mise à jour du devis.')

@section('content')
    @include('quotes._nav')
    @include('quotes._form', ['quote' => $quote])
@endsection
