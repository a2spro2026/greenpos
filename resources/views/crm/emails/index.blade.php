@extends('layouts.app')
@section('title', 'Emails CRM')
@section('breadcrumb', 'CRM / Emails')
@section('heading', 'Emails & modèles')
@section('content')
@include('crm._nav')
<div class="grid gap-4 xl:grid-cols-3">
    <article class="gp-card xl:col-span-1 space-y-3">
        <h2 class="text-sm font-bold">Modèles</h2>
        @foreach($templates as $t)
            <div class="rounded-lg border border-gp-border px-3 py-2">
                <p class="font-semibold text-sm">{{ $t->name }}</p>
                <p class="text-xs text-gp-muted">{{ $t->subject }}</p>
            </div>
        @endforeach
        <form method="POST" action="{{ route('crm.emails.store') }}" class="space-y-2 border-t border-gp-border pt-4">
            @csrf
            <p class="text-xs font-bold uppercase text-gp-muted">Enregistrer un envoi</p>
            <select name="crm_email_template_id" class="gp-input text-sm"><option value="">Sans modèle</option>@foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
            <input type="email" name="to_email" class="gp-input" placeholder="destinataire@" required>
            <input name="subject" class="gp-input" placeholder="Objet" required>
            <textarea name="body" rows="4" class="gp-input" placeholder="Corps…"></textarea>
            <button class="gp-btn-primary w-full">Logger l’email</button>
        </form>
    </article>
    <article class="gp-card overflow-hidden p-0 xl:col-span-2">
        <div class="border-b border-gp-border px-5 py-4"><h2 class="text-sm font-bold">Historique & suivi d’ouverture</h2></div>
        <table class="gp-table">
            <thead><tr><th>À</th><th>Objet</th><th>Statut</th><th>Envoyé</th><th>Ouvertures</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="text-sm">{{ $log->to_email }}</td>
                    <td>{{ $log->subject }}</td>
                    <td>{{ $log->statusLabel() }}</td>
                    <td class="text-gp-muted">{{ optional($log->sent_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->open_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-12 text-center text-gp-muted">Aucun email</td></tr>
            @endforelse
            </tbody>
        </table>
    </article>
</div>
@endsection
