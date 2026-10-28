@extends('layouts.app')

@section('title', 'Nouvelle dépense')
@section('breadcrumb', 'Comptabilité / Dépenses')
@section('heading', 'Nouvelle dépense')
@section('subtitle', 'Enregistrez une charge fixe ou variable.')

@section('actions')
    <a href="{{ route('accounting.expenses.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('accounting._nav')
    @include('accounting.expenses._form')
@endsection
