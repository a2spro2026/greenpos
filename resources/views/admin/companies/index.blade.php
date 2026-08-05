@extends('layouts.admin')

@section('title', 'Entreprises')
@section('breadcrumb', 'Pilotage')
@section('heading', 'Entreprises')
@section('actions')
    <a href="{{ route('admin.companies.create') }}" class="pa-btn pa-btn-primary">+ Nouvelle entreprise</a>
@endsection

@section('content')
<form method="GET" class="pa-card mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="pa-input max-w-xs">
    <select name="status" class="pa-select max-w-[10rem]">
        <option value="">Tous statuts</option>
        @foreach(['active' => 'Active', 'inactive' => 'Suspendue', 'archived' => 'Archivée'] as $val => $label)
            <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="pa-btn pa-btn-ghost" type="submit">Filtrer</button>
</form>

<div class="pa-card overflow-x-auto !p-0">
    <table class="pa-table">
        <thead>
            <tr>
                <th>Entreprise</th>
                <th>Contact</th>
                <th>Boutiques</th>
                <th>Plan</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $company)
                @php $tenant = $tenants[$company->id] ?? null; @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.companies.show', $company) }}" class="font-semibold text-white hover:text-emerald-300">{{ $company->name }}</a>
                        <div class="text-xs text-zinc-500">{{ $company->activity ?: '—' }} · {{ $company->city ?: '—' }}</div>
                    </td>
                    <td>
                        <div class="text-sm">{{ $company->email }}</div>
                        <div class="text-xs text-zinc-500">{{ $company->phone }}</div>
                    </td>
                    <td>{{ $company->stores_count }}</td>
                    <td>{{ $tenant?->currentSubscription?->plan?->name ?? '—' }}</td>
                    <td>
                        <span class="pa-badge {{ $company->status === 'active' ? 'pa-badge-ok' : ($company->status === 'inactive' ? 'pa-badge-danger' : 'pa-badge-muted') }}">{{ $company->status }}</span>
                    </td>
                    <td class="whitespace-nowrap text-right space-x-2">
                        <a href="{{ route('admin.companies.show', $company) }}" class="text-xs font-semibold text-emerald-400">Voir</a>
                        <a href="{{ route('admin.companies.edit', $company) }}" class="text-xs font-semibold text-zinc-300">Modifier</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-zinc-500">Aucune entreprise. Créez la première.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($companies->hasPages())
    <div class="mt-4">{{ $companies->links() }}</div>
@endif
@endsection
