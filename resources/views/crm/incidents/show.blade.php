@extends('layouts.app')
@section('heading', $incident->number)
@section('subtitle', $incident->subject)
@section('content')
@include('crm._nav')
<article class="gp-card max-w-2xl space-y-3 text-sm">
    <p>{{ $incident->typeLabel() }} · {{ $incident->priorityLabel() }} · {{ $incident->statusLabel() }}</p>
    <p>Assigné : {{ $incident->assignee?->name ?? '—' }}</p>
    <p>Client : {{ $incident->customer?->name ?? '—' }}</p>
    @if($incident->body)<p class="rounded-lg bg-gp-surface-2 p-3">{{ $incident->body }}</p>@endif
    <form method="POST" action="{{ route('crm.incidents.update', $incident) }}" class="grid gap-2 sm:grid-cols-3">
        @csrf @method('PUT')
        <select name="status" class="gp-select">@foreach($statuses as $k => $v)<option value="{{ $k }}" @selected($incident->status === $k)>{{ $v }}</option>@endforeach</select>
        <input type="hidden" name="assignee_user_id" value="{{ $incident->assignee_user_id }}">
        <button class="gp-btn-primary">Mettre à jour</button>
    </form>
</article>
@endsection
