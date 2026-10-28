@extends('layouts.app')

@section('title', 'Modifier '.$employee->full_name)
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Modifier '.$employee->full_name)
@section('subtitle', $employee->code)

@section('actions')
    <a href="{{ route('hr.employees.show', $employee) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('hr._nav')
    @include('hr.employees._form')
@endsection
