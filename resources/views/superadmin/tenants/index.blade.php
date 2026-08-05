@extends('layouts.superadmin')
@section('title', 'Entreprises')
@section('breadcrumb', 'Platform / Entreprises')
@section('heading', 'Gestion des entreprises')
@section('actions')
    <a href="{{ route('superadmin.tenants.create') }}" class="sa-btn sa-btn-primary">Nouvelle entreprise</a>
@endsection
@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, email, domaine…" class="sa-input max-w-xs">
    <select name="status" class="sa-select max-w-[10rem]">
        <option value="">Actives (non archivées)</option>
        @foreach($statuses as $k => $v)
            <option value="{{ $k }}" {{ ($filters['status'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    <button class="sa-btn sa-btn-ghost">Filtrer</button>
</form>
<div class="sa-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>Entreprise</th>
                    <th>Domaine</th>
                    <th>Abonnement</th>
                    <th>Statut</th>
                    <th>Inscription</th>
                    <th>Stockage</th>
                    <th>Users</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($tenants as $t)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            @if($t->company?->logoUrl())
                                <img src="{{ $t->company->logoUrl() }}" alt="" class="h-9 w-9 rounded-lg object-cover">
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-500/15 text-xs font-bold text-sky-300">{{ strtoupper(substr($t->name, 0, 2)) }}</span>
                            @endif
                            <div>
                                <a href="{{ route('superadmin.tenants.show', $t) }}" class="font-semibold text-sky-300 hover:underline">{{ $t->name }}</a>
                                <p class="sa-mono text-[10px] text-slate-500">{{ $t->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="sa-mono text-xs text-slate-400">{{ $t->domainLabel() }}</td>
                    <td>
                        <p class="font-medium text-white">{{ $t->currentSubscription?->plan?->name ?? '—' }}</p>
                        <p class="text-[11px] text-slate-500">{{ $t->currentSubscription?->billing_cycle === 'yearly' ? 'Annuel' : ($t->currentSubscription ? 'Mensuel' : '') }}</p>
                    </td>
                    <td><span class="sa-badge {{ $t->statusColor() }}">{{ $t->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ $t->created_at->format('d/m/Y') }}</td>
                    <td class="text-xs text-slate-400">{{ $t->storageLabel() }}</td>
                    <td class="font-semibold text-white">{{ $t->usersCount() }}</td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('superadmin.tenants.edit', $t) }}" class="text-xs font-semibold text-slate-400 hover:text-white">Éditer</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-16 text-center text-slate-500">Aucune entreprise SaaS.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($tenants->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $tenants->links() }}</div>@endif
</div>
@endsection
