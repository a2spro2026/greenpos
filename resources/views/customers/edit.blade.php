@extends('layouts.app')

@section('title', 'Modifier '.$customer->name)
@section('breadcrumb', 'Relation Client')
@section('heading', 'Modifier '.$customer->name)
@section('subtitle', $customer->code)

@section('actions')
    <a href="{{ route('customers.show', $customer) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('customers._nav')
    @include('customers._form')
@endsection
