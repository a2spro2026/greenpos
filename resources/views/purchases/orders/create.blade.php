@extends('layouts.app')

@section('title', 'Nouveau bon de commande')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Nouveau bon de commande')
@section('subtitle', 'Créez une commande fournisseur avec calcul automatique HT / TVA / TTC.')

@section('actions')
    <a href="{{ route('purchases.orders.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('purchases._nav')
    @include('purchases.orders._form')
@endsection
