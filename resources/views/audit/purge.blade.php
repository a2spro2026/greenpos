@extends('layouts.app')

@section('title', 'Purge du journal')
@section('breadcrumb', 'Administration / Audit')
@section('heading', 'Purger les anciens journaux')
@section('subtitle', 'Action irréversible réservée aux administrateurs autorisés. Les événements critiques sont conservés.')

@section('actions')
    <a href="{{ route('audit.index') }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('audit._nav')

    <form method="POST" action="{{ route('audit.purge.run') }}" class="gp-card max-w-xl space-y-4" onsubmit="return confirm('Confirmer la purge définitive ?')">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-semibold">Conserver les N derniers jours</label>
            <input type="number" name="days" value="{{ old('days', 365) }}" min="30" max="3650" class="gp-input w-40" required>
            <p class="mt-1 text-xs text-gp-muted">Minimum 30 jours. Les événements plus anciens (hors critiques) seront supprimés.</p>
            @error('days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="confirm" value="1" class="mt-1 rounded border-gp-border" required>
            <span>Je comprends que cette action est définitive et peut affecter la conformité.</span>
        </label>
        @error('confirm')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        <button class="gp-btn-primary bg-rose-600 hover:bg-rose-700">Purger</button>
    </form>
@endsection
