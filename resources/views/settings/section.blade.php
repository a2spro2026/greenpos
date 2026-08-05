@extends('layouts.app')

@section('title', $title)
@section('breadcrumb', 'Paramètres / '.$title)
@section('heading', $title)
@section('subtitle', 'Configurez les options de cette section.')

@section('content')
    <div class="flex flex-col gap-6 lg:flex-row">
        @include('settings._nav')

        <div class="min-w-0 flex-1">
            <form method="POST" action="{{ route('settings.section.update', $group) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('settings.partials.'.$group)

                @can('settings.update')
                <div class="flex justify-end">
                    <button type="submit" class="gp-btn-primary">Enregistrer</button>
                </div>
                @endcan
            </form>
        </div>
    </div>
@endsection
