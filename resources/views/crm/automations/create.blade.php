@extends('layouts.app')
@section('title','Nouvelle automatisation')
@section('heading','Nouvelle automatisation')
@section('content')
<section class="gp-card max-w-xl">
<form method="post" action="{{ route('crm.automations.store') }}" class="space-y-3">@csrf
<label class="block text-sm font-semibold">Nom<input name="name" required class="mt-1 w-full rounded-xl border px-3 py-2.5"></label>
<label class="block text-sm font-semibold">Déclencheur<select name="trigger_type" class="mt-1 w-full rounded-xl border px-3 py-2.5">@foreach($triggers as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
<label class="block text-sm font-semibold">Action<select name="action_type" class="mt-1 w-full rounded-xl border px-3 py-2.5">
<option value="create_incident">Créer incident</option>
<option value="create_task">Créer tâche</option>
<option value="send_reminder">Envoyer rappel</option>
<option value="notify">Notification interne</option>
</select></label>
<label class="block text-sm font-semibold">Titre action<input name="action_title" class="mt-1 w-full rounded-xl border px-3 py-2.5"></label>
<label class="block text-sm font-semibold">Message<textarea name="action_message" class="mt-1 w-full rounded-xl border px-3 py-2.5" rows="3"></textarea></label>
<label class="block text-sm font-semibold">Canal rappel<select name="action_channel" class="mt-1 w-full rounded-xl border px-3 py-2.5"><option value="notification">Notification</option><option value="email">Email</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select></label>
<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
<button class="gp-btn-primary">Créer</button>
</form></section>
@endsection
