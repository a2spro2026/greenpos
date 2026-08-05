@extends('layouts.app')

@section('title', 'Nouveau rôle')
@section('breadcrumb', 'Administration / Rôles / Nouveau')
@section('heading', 'Créer un rôle')
@section('subtitle', 'Définissez le nom et la matrice de permissions.')

@section('content')
    @include('roles._nav')

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        <section class="gp-card mb-6">
            <h2 class="mb-4 text-sm font-bold">Informations</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="gp-label">Nom *</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="gp-input w-full" required>
                </div>
                <div>
                    <label class="gp-label">Couleur</label>
                    <select name="color" class="gp-select w-full">
                        @foreach($colors as $c)
                            <option value="{{ $c }}" {{ old('color', 'slate') === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="gp-label">Description</label>
                    <textarea name="description" rows="2" class="gp-input w-full">{{ old('description') }}</textarea>
                </div>
            </div>
        </section>

        @include('roles._permissions')

        <div class="flex justify-end gap-3">
            <a href="{{ route('roles.index') }}" class="gp-btn-secondary">Annuler</a>
            <button class="gp-btn-primary">Créer le rôle</button>
        </div>
    </form>
@endsection
