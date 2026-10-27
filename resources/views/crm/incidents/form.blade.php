@extends('layouts.app')
@section('heading', 'Nouvel incident')
@section('content')
@include('crm._nav')
<form method="POST" action="{{ route('crm.incidents.store') }}" class="gp-card max-w-2xl space-y-4">
    @csrf
    <input name="subject" class="gp-input w-full" placeholder="Sujet" required>
    <div class="grid gap-3 sm:grid-cols-2">
        <select name="type" class="gp-select">@foreach($types as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
        <select name="priority" class="gp-select">@foreach($priorities as $k => $v)<option value="{{ $k }}" @selected($k==='normal')>{{ $v }}</option>@endforeach</select>
        <select name="customer_id" class="gp-select"><option value="">Client</option>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <select name="assignee_user_id" class="gp-select"><option value="">Assignation auto</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
    </div>
    <textarea name="body" rows="4" class="gp-input w-full" placeholder="Description"></textarea>
    <button class="gp-btn-primary">Ouvrir</button>
</form>
@endsection
