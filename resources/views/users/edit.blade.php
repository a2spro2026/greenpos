@extends('layouts.app')

@section('title', 'Modifier ' . $user->displayName())
@section('breadcrumb', 'Administration / Utilisateurs / Modifier')
@section('heading', 'Modifier ' . $user->displayName())
@section('subtitle', $user->email)

@section('content')
    @include('users._nav')
    @include('users._form')
@endsection
