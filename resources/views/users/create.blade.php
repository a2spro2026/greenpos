@extends('layouts.app')

@section('title', 'Nouvel utilisateur')
@section('breadcrumb', 'Administration / Utilisateurs / Nouveau')
@section('heading', 'Créer un utilisateur')
@section('subtitle', 'Ajoutez un collaborateur à l\'entreprise.')

@section('content')
    @include('users._nav')
    @include('users._form')
@endsection
