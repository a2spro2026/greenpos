@extends('layouts.app')
@section('title', 'Nouveau rappel')
@section('heading', 'Nouveau rappel')
@section('content')
<section class="gp-card max-w-xl">
<form method="post" action="{{ route('messaging.reminders.store') }}" class="space-y-4">@csrf
<label class="block text-sm font-semibold">Canal
<select name="channel" class="mt-1 w-full rounded-xl border border-gp-border px-3 py-2.5" required>
@foreach($channels as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
</select></label>
<label class="block text-sm font-semibold">Destinataire<input name="recipient" required class="mt-1 w-full rounded-xl border border-gp-border px-3 py-2.5" placeholder="email / téléphone"></label>
<label class="block text-sm font-semibold">Message<textarea name="message" rows="4" required class="mt-1 w-full rounded-xl border border-gp-border px-3 py-2.5"></textarea></label>
<label class="block text-sm font-semibold">Planifié le<input type="datetime-local" name="scheduled_at" class="mt-1 w-full rounded-xl border border-gp-border px-3 py-2.5"></label>
<button class="gp-btn-primary">Enregistrer</button>
</form></section>
@endsection
