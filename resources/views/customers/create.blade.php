@extends('layouts.app')

@section('title', 'Nouveau client')
@section('breadcrumb', 'Relation Client')
@section('heading', 'Nouveau client')
@section('subtitle', 'Particulier ou société — fiche complète.')

@section('actions')
    <a href="{{ route('customers.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('customers._nav')
    @include('customers._form')
@endsection
