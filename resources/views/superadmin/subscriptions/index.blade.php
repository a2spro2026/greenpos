@extends('layouts.superadmin')
@section('title', 'Liste abonnements')
@section('breadcrumb', 'Billing / Abonnements')
@section('heading', 'Abonnements')
@section('actions')
    <a href="{{ route('superadmin.subscriptions.dashboard') }}" class="sa-btn sa-btn-ghost">Dashboard</a>
    <a href="{{ route('superadmin.subscriptions.create') }}" class="sa-btn sa-btn-primary">Créer</a>
@endsection
@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Client…" class="sa-input max-w-xs">
    <select name="status" class="sa-select max-w-[12rem]">
        <option value="">Statut</option>
        @foreach($statuses as $k => $v)
            <option value="{{ $k }}" {{ ($filters['status'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    <select name="plan_id" class="sa-select max-w-[12rem]">
        <option value="">Plan</option>
        @foreach($plans as $p)
            <option value="{{ $p->id }}" {{ ($filters['plan_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
        @endforeach
    </select>
    <button class="sa-btn sa-btn-ghost">Filtrer</button>
</form>
<div class="sa-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>Client</th><th>Plan</th><th>Cycle</th><th>Montant</th>
                    <th>Statut</th><th>Fin</th><th>Auto</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($subscriptions as $s)
                <tr>
                    <td>
                        <a href="{{ route('superadmin.subscriptions.show', $s) }}" class="font-semibold text-sky-300 hover:underline">{{ $s->tenant?->name }}</a>
                        @if($s->isExpiringSoon())
                            <span class="sa-badge bg-amber-500/15 text-amber-300 ml-1">Expire bientôt</span>
                        @endif
                    </td>
                    <td>{{ $s->plan?->name }}</td>
                    <td class="text-slate-400">{{ $s->billing_cycle === 'yearly' ? 'Annuel' : 'Mensuel' }}</td>
                    <td class="font-semibold">{{ number_format($s->amount, 2, ',', ' ') }} {{ $s->currency }}</td>
                    <td><span class="sa-badge {{ $s->statusColor() }}">{{ $s->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ optional($s->ends_at)->format('d/m/Y') }}</td>
                    <td class="text-slate-400">{{ $s->auto_renew ? 'Oui' : 'Non' }}</td>
                    <td class="text-right"><a href="{{ route('superadmin.subscriptions.edit', $s) }}" class="text-xs text-slate-400 hover:text-white">Éditer</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-16 text-center text-slate-500">Aucun abonnement</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($subscriptions->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $subscriptions->links() }}</div>@endif
</div>
@endsection
