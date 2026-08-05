@extends('layouts.admin')

@section('title', 'Demandes d’inscription')
@section('breadcrumb', 'Acquisition')
@section('heading', 'Demandes d’inscription')
@section('actions')
    <a href="{{ route('register-company') }}" target="_blank" class="pa-btn pa-btn-ghost">Page publique</a>
    <a href="{{ route('admin.companies.create') }}" class="pa-btn pa-btn-primary">+ Nouvelle entreprise</a>
@endsection

@section('content')
<div class="pa-grid-4 mb-4">
    <div class="pa-kpi">
        <div class="label">En attente</div>
        <div class="value">{{ number_format($counts['pending'] ?? 0) }}</div>
    </div>
    <div class="pa-kpi">
        <div class="label">Actives</div>
        <div class="value">{{ number_format($counts['active'] ?? 0) }}</div>
    </div>
    <div class="pa-kpi">
        <div class="label">Suspendues</div>
        <div class="value">{{ number_format($counts['suspended'] ?? 0) }}</div>
    </div>
    <div class="pa-kpi">
        <div class="label">Refusées</div>
        <div class="value">{{ number_format($counts['rejected'] ?? 0) }}</div>
    </div>
</div>

<form method="GET" class="pa-card mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="pa-input max-w-xs">
    <select name="status" class="pa-select max-w-[12rem]">
        <option value="">Tous statuts</option>
        @foreach(\App\Models\CompanyRegistrationRequest::STATUSES as $val => $label)
            <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="pa-btn pa-btn-ghost" type="submit">Filtrer</button>
</form>

<div class="pa-card overflow-x-auto !p-0">
    <table class="pa-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Entreprise</th>
                <th>Responsable</th>
                <th>Email</th>
                <th>Activité</th>
                <th>Plan demandé</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td class="whitespace-nowrap text-xs text-zinc-500">{{ $item->created_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.registrations.show', $item) }}" class="font-semibold text-white hover:text-emerald-300">{{ $item->company_name }}</a>
                        <div class="text-xs text-zinc-500">{{ $item->reference }}</div>
                    </td>
                    <td>{{ $item->owner_name }}</td>
                    <td>{{ $item->owner_email }}</td>
                    <td>{{ $item->activity ?: '—' }}</td>
                    <td>{{ $item->plan?->name ?? '—' }}</td>
                    <td>
                        @php
                            $badge = match($item->status) {
                                'EN_ATTENTE' => 'pa-badge-warn',
                                'ACTIVE' => 'pa-badge-ok',
                                'REFUSEE' => 'pa-badge-danger',
                                'SUSPENDUE' => 'pa-badge-muted',
                                default => 'pa-badge-muted',
                            };
                        @endphp
                        <span class="pa-badge {{ $badge }}">{{ $item->statusLabel() }}</span>
                    </td>
                    <td class="whitespace-nowrap text-right space-x-2">
                        <a href="{{ route('admin.registrations.show', $item) }}" class="text-xs font-semibold text-emerald-400">Voir</a>
                        @if($item->canApprove())
                            <form method="POST" action="{{ route('admin.registrations.approve', $item) }}" class="inline">
                                @csrf
                                <button class="text-xs font-semibold text-emerald-300" type="submit" onclick="return confirm('Approuver et créer l’entreprise ?')">Approuver</button>
                            </form>
                        @endif
                        @if($item->canReject())
                            <a href="{{ route('admin.registrations.show', $item) }}#reject" class="text-xs font-semibold text-rose-400">Refuser</a>
                        @endif
                        @if($item->canSuspend())
                            <a href="{{ route('admin.registrations.show', $item) }}#suspend" class="text-xs font-semibold text-amber-400">Suspendre</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-zinc-500">Aucune demande d’inscription.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($items->hasPages())
    <div class="mt-4">{{ $items->links() }}</div>
@endif
@endsection
