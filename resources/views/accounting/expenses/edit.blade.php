@extends('layouts.app')

@section('title', 'Modifier dépense')
@section('breadcrumb', 'Comptabilité / Dépenses')
@section('heading', 'Modifier la dépense')
@section('subtitle', $expense->label)

@section('actions')
    <a href="{{ route('accounting.expenses.show', $expense) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('accounting._nav')
    @include('accounting.expenses._form')
@endsection
