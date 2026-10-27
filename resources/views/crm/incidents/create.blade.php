@extends('layouts.app')
@section('title','Nouvel incident')
@section('heading','Nouvel incident')
@section('content')
<section class="gp-card max-w-xl">
<form method="post" action="{{ route('crm.incidents.store') }}" class="space-y-3">@csrf
<label class="block text-sm font-semibold">Titre<input name="title" required class="mt-1 w-full rounded-xl border px-3 py-2.5"></label>
<label class="block text-sm font-semibold">Type<input name="type" class="mt-1 w-full rounded-xl border px-3 py-2.5"></label>
<label class="block text-sm font-semibold">Priorité<select name="priority" class="mt-1 w-full rounded-xl border px-3 py-2.5">@foreach($priorities as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
<label class="block text-sm font-semibold">Assigné à<select name="assigned_to" class="mt-1 w-full rounded-xl border px-3 py-2.5"><option value="">—</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></label>
<label class="block text-sm font-semibold">Description<textarea name="description" rows="4" class="mt-1 w-full rounded-xl border px-3 py-2.5"></textarea></label>
<button class="gp-btn-primary">Créer</button>
</form></section>
@endsection
