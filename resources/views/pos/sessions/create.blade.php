@extends('layouts.app')

@section('title', 'Ouvrir caisse')
@section('breadcrumb', 'Ventes / POS / Sessions')
@section('heading', 'Ouverture de caisse')
@section('subtitle', 'Déclarez le fond de caisse avant d’encaisser.')

@section('content')
    @include('pos._nav')

    @if($current)
        <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
            Une caisse est déjà ouverte ({{ $current->number }}).
            <a href="{{ route('pos.terminal') }}" class="font-semibold underline">Aller au terminal</a>
        </div>
    @else
        <form method="post" action="{{ route('pos.sessions.store') }}" class="gp-card mx-auto max-w-lg space-y-4">
            @csrf
            <label class="block">
                <span class="mb-1 block text-sm font-semibold">Fond de caisse (espèces)</span>
                <input type="number" name="opening_float" value="{{ old('opening_float', 500) }}" min="0" step="0.01" required class="gp-input">
                @error('opening_float')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-semibold">Notes d’ouverture</span>
                <textarea name="opening_notes" rows="3" class="gp-input" placeholder="Optionnel…">{{ old('opening_notes') }}</textarea>
            </label>
            <div class="flex gap-2">
                <a href="{{ route('pos.sessions.index') }}" class="gp-btn-secondary">Annuler</a>
                <button type="submit" class="gp-btn-primary">Ouvrir la caisse</button>
            </div>
        </form>
    @endif
@endsection
