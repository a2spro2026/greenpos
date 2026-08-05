@extends('layouts.app')
@section('title', 'Leads CRM')
@section('breadcrumb', 'CRM / Leads')
@section('heading', 'Gestion des leads')
@section('actions')
    <a href="{{ route('crm.leads.create') }}" class="gp-btn-primary">Nouveau lead</a>
@endsection
@section('content')
@include('crm._nav')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, email, n°…" class="gp-input max-w-xs">
    <select name="type" class="gp-input max-w-[9rem]">
        <option value="">Type</option>
        @foreach($types as $k => $v)<option value="{{ $k }}" {{ ($filters['type'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
    </select>
    <select name="status" class="gp-input max-w-[10rem]">
        <option value="">Statut</option>
        @foreach($statuses as $k => $v)<option value="{{ $k }}" {{ ($filters['status'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
    </select>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="archived" value="1" {{ !empty($filters['archived']) ? 'checked' : '' }}> Archivés</label>
    <button class="gp-btn-secondary">Filtrer</button>
</form>
<div class="gp-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="gp-table">
            <thead><tr><th>Lead</th><th>Type</th><th>Source</th><th>Owner</th><th>Valeur</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            @forelse($leads as $l)
                <tr>
                    <td>
                        <a href="{{ route('crm.leads.show', $l) }}" class="font-semibold text-gp-primary hover:underline">{{ $l->displayName() }}</a>
                        <p class="text-xs text-gp-muted">{{ $l->number }} · {{ $l->email }}</p>
                    </td>
                    <td>{{ $l->typeLabel() }}</td>
                    <td class="text-gp-muted">{{ $l->sourceLabel() }}</td>
                    <td class="text-sm">{{ $l->owner?->name ?: '—' }}</td>
                    <td>{{ number_format($l->estimated_value, 0, ',', ' ') }}</td>
                    <td><span class="gp-badge {{ $l->statusColor() }}">{{ $l->statusLabel() }}</span></td>
                    <td class="text-right"><a href="{{ route('crm.leads.edit', $l) }}" class="text-xs font-semibold text-gp-muted hover:text-gp-text">Éditer</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-16 text-center text-gp-muted">Aucun lead</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($leads->hasPages())<div class="border-t border-gp-border px-4 py-3">{{ $leads->links() }}</div>@endif
</div>
@endsection
