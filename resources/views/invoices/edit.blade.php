@extends('layouts.app')

@section('title', $invoice->number)
@section('breadcrumb', 'Finance / Facturation')
@section('heading', 'Modifier '.$invoice->number)
@section('subtitle', 'Brouillon modifiable.')

@section('content')
    @include('invoices._nav')
    @include('invoices._form', ['invoice' => $invoice])
@endsection
