@extends('layouts.app')

@section('title', 'Modifier '.$order->number)
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Modifier '.$order->number)
@section('subtitle', 'Brouillon — modification des lignes et totaux.')

@section('actions')
    <a href="{{ route('purchases.orders.show', $order) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('purchases._nav')
    @include('purchases.orders._form')
@endsection
