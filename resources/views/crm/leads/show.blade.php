@extends('layouts.app')
@section('title', $lead->displayName())
@section('breadcrumb', 'CRM / Leads')
@section('heading', $lead->displayName())
@section('actions')
    <a href="{{ route('crm.leads.edit', $lead) }}" class="gp-btn-secondary">Modifier</a>
    @if(!in_array($lead->status, ['converted','archived'], true))
        <form method="POST" action="{{ route('crm.leads.qualify', $lead) }}">@csrf<button class="gp-btn-secondary">Qualifier</button></form>
        <form method="POST" action="{{ route('crm.leads.convert', $lead) }}">@csrf<input type="hidden" name="create_opportunity" value="1"><button class="gp-btn-primary">Convertir en client</button></form>
    @endif
@endsection
@section('content')
@include('crm._nav')
<div class="mb-4 flex flex-wrap gap-2">
    <span class="gp-badge {{ $lead->statusColor() }}">{{ $lead->statusLabel() }}</span>
    <span class="gp-badge bg-slate-100 text-slate-700">{{ $lead->typeLabel() }}</span>
    <span class="gp-badge bg-amber-100 text-amber-800">{{ $lead->ratingLabel() }}</span>
</div>

<div class="mb-6 grid gap-4 xl:grid-cols-3">
    <article class="gp-card space-y-3 text-sm xl:col-span-2">
        <h2 class="text-sm font-bold">Fiche</h2>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-gp-muted">N°</dt><dd class="font-mono text-xs">{{ $lead->number }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Source</dt><dd>{{ $lead->sourceLabel() }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Email</dt><dd>{{ $lead->email ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Téléphone</dt><dd>{{ $lead->phone ?: $lead->mobile ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Ville</dt><dd>{{ $lead->city ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Valeur estimée</dt><dd class="font-bold">{{ number_format($lead->estimated_value, 2, ',', ' ') }} {{ $lead->currency }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Commercial</dt><dd>{{ $lead->owner?->name ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Client lié</dt><dd>@if($lead->customer)<a href="{{ route('customers.show', $lead->customer) }}" class="text-gp-primary hover:underline">{{ $lead->customer->name }}</a>@else — @endif</dd></div>
            @if($lead->description)<div class="sm:col-span-2"><dt class="text-xs text-gp-muted">Notes</dt><dd>{{ $lead->description }}</dd></div>@endif
        </dl>

        <h3 class="pt-4 text-sm font-bold">Timeline activités</h3>
        <ul class="space-y-3">
            @forelse($lead->activities as $a)
                <li class="rounded-xl border border-gp-border px-3 py-2">
                    <p class="text-xs text-gp-muted">{{ $a->typeLabel() }} · {{ $a->created_at->format('d/m/Y H:i') }}</p>
                    <a href="{{ route('crm.activities.show', $a) }}" class="font-semibold hover:text-gp-primary">{{ $a->subject }}</a>
                </li>
            @empty
                <li class="text-sm text-gp-muted">Aucune activité</li>
            @endforelse
        </ul>
    </article>

    <aside class="space-y-4">
        <article class="gp-card">
            <h2 class="mb-2 text-sm font-bold">GreenPOS AI</h2>
            <pre class="whitespace-pre-wrap text-xs text-gp-muted">{{ $summary }}</pre>
            <button type="button" id="crm-ai-email" class="gp-btn-secondary mt-3 w-full text-xs" data-url="{{ route('crm.ai') }}" data-lead="{{ $lead->id }}">Rédiger un email (IA)</button>
            <pre id="crm-ai-out" class="mt-3 hidden whitespace-pre-wrap rounded-lg bg-gp-surface-2 p-3 text-xs"></pre>
        </article>
        <article class="gp-card space-y-2">
            <h2 class="text-sm font-bold">Actions</h2>
            <form method="POST" action="{{ route('crm.leads.assign', $lead) }}" class="space-y-2">
                @csrf
                <select name="owner_user_id" class="gp-input text-sm">
                    @foreach($users as $u)<option value="{{ $u->id }}" {{ (int)$lead->owner_user_id === $u->id ? 'selected' : '' }}>{{ $u->name ?: $u->email }}</option>@endforeach
                </select>
                <button class="gp-btn-secondary w-full">Affecter</button>
            </form>
            <a href="{{ route('crm.activities.create', ['lead_id' => $lead->id]) }}" class="gp-btn-secondary w-full text-center">Ajouter activité</a>
            <a href="{{ route('crm.opportunities.create', ['lead_id' => $lead->id]) }}" class="gp-btn-secondary w-full text-center">Créer opportunité</a>
            @unless($lead->archived_at)
                <form method="POST" action="{{ route('crm.leads.archive', $lead) }}" onsubmit="return confirm('Archiver ?')">@csrf<button class="gp-btn-secondary w-full text-rose-600">Archiver</button></form>
            @endunless
        </article>
        <article class="gp-card">
            <h2 class="mb-2 text-sm font-bold">Opportunités</h2>
            @forelse($lead->opportunities as $o)
                <a href="{{ route('crm.opportunities.show', $o) }}" class="mb-2 block text-sm font-semibold text-gp-primary">{{ $o->name }}</a>
            @empty
                <p class="text-xs text-gp-muted">Aucune</p>
            @endforelse
        </article>
    </aside>
</div>
<script>
document.getElementById('crm-ai-email')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const res = await fetch(btn.dataset.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ action: 'draft_email', lead_id: Number(btn.dataset.lead) })
    });
    const data = await res.json();
    const out = document.getElementById('crm-ai-out');
    out.textContent = data.content || '';
    out.classList.remove('hidden');
});
</script>
@endsection
