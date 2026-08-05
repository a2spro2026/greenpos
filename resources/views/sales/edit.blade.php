@extends('layouts.app')

@section('title', 'Modifier ' . $sale->number)
@section('breadcrumb', 'Ventes / Modifier')
@section('heading', 'Modifier ' . $sale->number)
@section('subtitle', 'Vente en brouillon modifiable.')

@section('content')
    @include('sales._nav')
    @include('sales._form')
@endsection
