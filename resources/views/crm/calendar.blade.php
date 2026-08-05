@extends('layouts.app')
@section('title', 'Calendrier CRM')
@section('breadcrumb', 'CRM / Calendrier')
@section('heading', 'Calendrier commercial')
@section('actions')
    <a href="{{ route('crm.activities.create') }}" class="gp-btn-primary">Planifier</a>
@endsection
@section('content')
@include('crm._nav')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="date" name="from" value="{{ $from }}" class="gp-input max-w-[11rem]">
    <input type="date" name="to" value="{{ $to }}" class="gp-input max-w-[11rem]">
    <button class="gp-btn-secondary">Afficher</button>
</form>
<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
@forelse($events as $e)
    <a href="{{ $e['url'] }}" class="gp-card block transition hover:-translate-y-0.5">
        <p class="text-[10px] font-bold uppercase tracking-wide text-teal-600">{{ $e['type_label'] }}</p>
        <h3 class="mt-1 font-bold text-gp-text">{{ $e['title'] }}</h3>
        <p class="mt-1 text-xs text-gp-muted">{{ $e['start'] ? \Illuminate\Support\Carbon::parse($e['start'])->format('d/m/Y H:i') : '—' }}</p>
        <p class="mt-1 text-xs text-gp-muted">{{ $e['lead'] ?: $e['opportunity'] ?: '' }}</p>
    </a>
@empty
    <div class="gp-card col-span-full py-16 text-center text-gp-muted">Aucun événement sur la période</div>
@endforelse
</div>
@endsection
