@extends('layouts.app')
@section('title', $opportunity->name)
@section('breadcrumb', 'CRM / Opportunités')
@section('heading', $opportunity->name)
@section('actions')
    <a href="{{ route('crm.pipeline') }}" class="gp-btn-secondary">Pipeline</a>
    <a href="{{ route('crm.activities.create', ['opportunity_id' => $opportunity->id]) }}" class="gp-btn-primary">Activité</a>
@endsection
@section('content')
@include('crm._nav')
<div class="mb-4 flex flex-wrap gap-2">
    <span class="gp-badge bg-slate-100 text-slate-800">{{ $opportunity->stageLabel() }}</span>
    <span class="gp-badge bg-teal-100 text-teal-800">{{ $opportunity->probability }}%</span>
    <span class="gp-badge bg-emerald-100 text-emerald-800">IA · {{ $estimate['probability'] }}%</span>
</div>
<div class="grid gap-4 xl:grid-cols-3">
    <article class="gp-card space-y-3 text-sm xl:col-span-2">
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-gp-muted">Montant</dt><dd class="text-xl font-bold">{{ number_format($opportunity->amount, 2, ',', ' ') }} {{ $opportunity->currency }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Pondéré</dt><dd>{{ number_format($opportunity->weightedAmount(), 2, ',', ' ') }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Lead</dt><dd>@if($opportunity->lead)<a href="{{ route('crm.leads.show', $opportunity->lead) }}" class="text-gp-primary">{{ $opportunity->lead->displayName() }}</a>@else — @endif</dd></div>
            <div><dt class="text-xs text-gp-muted">Client</dt><dd>@if($opportunity->customer)<a href="{{ route('customers.show', $opportunity->customer) }}" class="text-gp-primary">{{ $opportunity->customer->name }}</a>@else — @endif</dd></div>
            <div><dt class="text-xs text-gp-muted">Close</dt><dd>{{ optional($opportunity->expected_close_on)->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gp-muted">Owner</dt><dd>{{ $opportunity->owner?->name ?: '—' }}</dd></div>
            @if($opportunity->quote_id)<div><dt class="text-xs text-gp-muted">Devis</dt><dd><a href="{{ route('quotes.show', $opportunity->quote_id) }}" class="text-gp-primary">Voir devis</a></dd></div>@endif
            @if($opportunity->invoice_id)<div><dt class="text-xs text-gp-muted">Facture</dt><dd><a href="{{ route('invoices.show', $opportunity->invoice_id) }}" class="text-gp-primary">Voir facture</a></dd></div>@endif
        </dl>
        <h3 class="pt-2 text-sm font-bold">Timeline</h3>
        @forelse($opportunity->activities as $a)
            <div class="rounded-lg border border-gp-border px-3 py-2 text-sm"><a href="{{ route('crm.activities.show', $a) }}" class="font-semibold">{{ $a->subject }}</a> <span class="text-xs text-gp-muted">{{ $a->typeLabel() }}</span></div>
        @empty
            <p class="text-sm text-gp-muted">Aucune activité</p>
        @endforelse
    </article>
    <aside class="gp-card">
        <h2 class="mb-2 text-sm font-bold">Analyse IA</h2>
        <p class="text-3xl font-bold text-teal-600">{{ $estimate['probability'] }}%</p>
        <p class="mt-2 text-sm text-gp-muted">{{ $estimate['advice'] }}</p>
        <ul class="mt-3 space-y-1 text-xs text-gp-muted">
            <li>Étape : {{ $estimate['factors']['stage'] }}</li>
            <li>Base : {{ $estimate['factors']['base'] }} · Boost : {{ $estimate['factors']['boost'] }}</li>
        </ul>
        <div class="mt-4 space-y-2">
            @if(Route::has('quotes.create'))
                <a href="{{ route('quotes.create') }}" class="gp-btn-secondary w-full text-center text-xs">Créer un devis</a>
            @endif
            @if(Route::has('invoices.create'))
                <a href="{{ route('invoices.create') }}" class="gp-btn-secondary w-full text-center text-xs">Créer une facture</a>
            @endif
        </div>
    </aside>
</div>
@endsection
