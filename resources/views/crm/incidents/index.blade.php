@extends('layouts.app')
@section('title', 'Incidents')
@section('heading', 'Incidents')
@section('subtitle', 'Affectation automatique par type.')
@section('actions')
    <a href="{{ route('crm.incidents.create') }}" class="gp-btn-primary">Nouvel incident</a>
@endsection
@section('content')
@include('crm._nav')
<section class="mb-4 grid gap-3 sm:grid-cols-4">
    <article class="gp-kpi"><p class="text-xs text-gp-muted">Ouverts</p><p class="text-2xl font-bold">{{ $stats['open'] }}</p></article>
    <article class="gp-kpi"><p class="text-xs text-gp-muted">En cours</p><p class="text-2xl font-bold">{{ $stats['in_progress'] }}</p></article>
    <article class="gp-kpi"><p class="text-xs text-gp-muted">Résolus</p><p class="text-2xl font-bold">{{ $stats['resolved'] }}</p></article>
    <article class="gp-kpi"><p class="text-xs text-gp-muted">Total</p><p class="text-2xl font-bold">{{ $stats['total'] }}</p></article>
</section>
<section class="gp-card mb-4">
    <h2 class="mb-3 text-sm font-bold">Affectation par type</h2>
    <form method="POST" action="{{ route('crm.incidents.assign') }}" class="grid gap-2 sm:grid-cols-3">
        @csrf
        <select name="incident_type" class="gp-select">@foreach($types as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
        <select name="user_id" class="gp-select">@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
        <button class="gp-btn-secondary">Enregistrer</button>
    </form>
    <ul class="mt-3 text-sm text-gp-muted">
        @foreach($types as $k => $v)
            <li>{{ $v }} → {{ $assignments[$k]->user->name ?? 'non assigné' }}</li>
        @endforeach
    </ul>
</section>
<section class="gp-card overflow-hidden p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-gp-muted dark:bg-white/5"><tr><th class="px-4 py-3 text-left">N°</th><th class="px-4 py-3 text-left">Sujet</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Assigné</th><th class="px-4 py-3 text-left">Statut</th></tr></thead>
        <tbody>
            @foreach($incidents as $incident)
                <tr class="border-t border-gp-border dark:border-white/10">
                    <td class="px-4 py-3"><a class="text-gp-primary" href="{{ route('crm.incidents.show', $incident) }}">{{ $incident->number }}</a></td>
                    <td class="px-4 py-3">{{ $incident->subject }}</td>
                    <td class="px-4 py-3">{{ $incident->typeLabel() }}</td>
                    <td class="px-4 py-3">{{ $incident->assignee?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $incident->statusLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $incidents->links() }}</div>
</section>
@endsection
