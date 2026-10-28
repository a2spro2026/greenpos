@extends('layouts.app')

@section('title', 'Nouvel employé')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Nouvel employé')
@section('subtitle', 'Fiche collaborateur complète.')

@section('actions')
    <a href="{{ route('hr.employees.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('hr._nav')
    @include('hr.employees._form')
@endsection
