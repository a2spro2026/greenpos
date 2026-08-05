@extends('layouts.app')

@section('title', 'Documents liés')
@section('breadcrumb', 'Documents / Liés')
@section('heading', 'Documents liés')
@section('subtitle', ucfirst($type).' #'.$id)

@section('actions')
    @can('documents.create')
        <a href="{{ route('documents.upload', ['documentable_type' => $type, 'documentable_id' => $id]) }}" class="gp-btn-primary">Ajouter</a>
    @endcan
@endsection

@section('content')
    @include('documents._nav')
    @include('documents._related', ['type' => $type, 'id' => $id])
@endsection
