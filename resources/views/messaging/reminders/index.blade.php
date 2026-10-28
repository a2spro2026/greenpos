@extends('layouts.app')
@section('title', 'Rappels')
@section('heading', 'Rappels')
@section('subtitle', 'Email, SMS, WhatsApp et notifications.')
@section('actions')
<div class="flex gap-2">
<a href="{{ route('messaging.reminders.create') }}" class="gp-btn-primary">Nouveau rappel</a>
<form method="post" action="{{ route('messaging.reminders.process') }}">@csrf<button class="gp-btn-secondary">Traiter dus ({{ $pending }})</button></form>
</div>
@endsection
@section('content')
@if(session('success'))<div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm">{{ session('warning') }}</div>@endif
<section class="gp-card overflow-hidden p-0">
<table class="min-w-full text-sm">
<thead class="border-b bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">Canal</th><th class="px-4 py-3">Destinataire</th><th class="px-4 py-3">Message</th><th class="px-4 py-3">Planifié</th><th class="px-4 py-3">Statut</th><th></th></tr></thead>
<tbody>
@forelse($items as $r)
<tr class="border-t border-gp-border">
<td class="px-4 py-3">{{ $channels[$r->channel] ?? $r->channel }}</td>
<td class="px-4 py-3">{{ $r->recipient }}</td>
<td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($r->message, 70) }}</td>
<td class="px-4 py-3">{{ optional($r->scheduled_at)->format('d/m/Y H:i') }}</td>
<td class="px-4 py-3">{{ $statuses[$r->status] ?? $r->status }}</td>
<td class="px-4 py-3 text-right space-x-2">
@if($r->status === 'pending')
<form class="inline" method="post" action="{{ route('messaging.reminders.send', $r) }}">@csrf<button class="font-semibold text-emerald-700">Envoyer</button></form>
<form class="inline" method="post" action="{{ route('messaging.reminders.mark', $r) }}">@csrf<button class="text-sky-700">Marquer</button></form>
<form class="inline" method="post" action="{{ route('messaging.reminders.cancel', $r) }}">@csrf<button class="text-rose-700">Annuler</button></form>
@endif
</td></tr>
@empty
<tr><td colspan="6" class="px-4 py-12 text-center text-gp-muted">Aucun rappel</td></tr>
@endforelse
</tbody></table></section>
{{ $items->links() }}
@endsection
