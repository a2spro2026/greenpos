@extends('layouts.app')

@section('title', 'Modifier ' . $role->name)
@section('breadcrumb', 'Administration / Rôles / Modifier')
@section('heading', 'Modifier — ' . $role->name)
@section('subtitle', $role->is_system ? 'Les modifications créent un override entreprise du rôle système.' : 'Rôle personnalisé')

@section('content')
    @include('roles._nav')

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('roles.update', $sourceRole) }}">
        @csrf
        @method('PUT')
        <section class="gp-card mb-6">
            <h2 class="mb-4 text-sm font-bold">Informations</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="gp-label">Nom *</label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" class="gp-input w-full" required>
                </div>
                <div>
                    <label class="gp-label">Couleur</label>
                    <select name="color" class="gp-select w-full">
                        @foreach($colors as $c)
                            <option value="{{ $c }}" {{ old('color', $role->color) === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="gp-label">Description</label>
                    <textarea name="description" rows="2" class="gp-input w-full">{{ old('description', $role->description) }}</textarea>
                </div>
            </div>
        </section>

        @include('roles._permissions', ['selected' => old('permissions', $selected)])

        <div class="flex justify-end gap-3">
            <a href="{{ route('roles.show', $role) }}" class="gp-btn-secondary">Annuler</a>
            <button class="gp-btn-primary">Enregistrer</button>
        </div>
    </form>
@endsection
