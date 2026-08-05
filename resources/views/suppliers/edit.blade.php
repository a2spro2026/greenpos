@extends('layouts.app')

@section('title', 'Modifier '.$supplier->name)
@section('breadcrumb', 'Approvisionnement / Fournisseurs')
@section('heading', 'Modifier '.$supplier->name)
@section('subtitle', $supplier->code)

@section('actions')
    <a href="{{ route('suppliers.show', $supplier) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('suppliers._nav')
    @include('suppliers._form')
@endsection
