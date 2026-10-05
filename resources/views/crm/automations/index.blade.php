@extends('layouts.app')
@section('title', 'Automatisations')
@section('heading', 'Automatisations')
@section('subtitle', 'Stock faible, seuil de ventes, planification. Les tâches sont des activités CRM.')
@section('actions')
    <form method="POST" action="{{ route('crm.automations.run') }}">@csrf<button class="gp-btn-secondary">Exécuter maintenant</button></form>
@endsection
@section('content')
@include('crm._nav')
@if(session('success'))<div class="mb-4 gp-flash gp-flash-success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ route('crm.automations.store') }}" class="gp-card mb-4 grid gap-3 sm:grid-cols-4">
    @csrf
    <input name="name" placeholder="Nom" class="gp-input" required>
    <select name="trigger" class="gp-select">@foreach($triggers as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
    <input type="number" step="0.01" name="amount" placeholder="Seuil MAD" class="gp-input">
    <button class="gp-btn-primary">Ajouter</button>
    <input type="number" name="hour" min="0" max="23" placeholder="Heure" class="gp-input">
    <input name="subject" placeholder="Sujet de tâche" class="gp-input sm:col-span-2">
    <label class="text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
</form>
<section class="gp-card space-y-3">
    @foreach($rules as $rule)
        <form method="POST" action="{{ route('crm.automations.update', $rule) }}" class="flex flex-wrap items-center justify-between gap-3 border-b border-gp-border pb-3 dark:border-white/10">
            @csrf @method('PUT')
            <div>
                <p class="font-semibold">{{ $rule->name }}</p>
                <p class="text-xs text-gp-muted">{{ $rule->triggerLabel() }} · dernière exécution {{ optional($rule->last_ran_at)->format('d/m H:i') ?: '—' }}</p>
            </div>
            <label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> Active</label>
            <button class="gp-btn-secondary text-xs">Enregistrer</button>
        </form>
    @endforeach
</section>
@endsection
