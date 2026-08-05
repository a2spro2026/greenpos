@extends('layouts.superadmin')
@section('title', 'Factures SaaS')
@section('breadcrumb', 'Billing / Factures')
@section('heading', 'Factures SaaS')
@section('actions')
    <a href="{{ route('superadmin.invoices.create') }}" class="sa-btn sa-btn-primary">Émettre une facture</a>
@endsection
@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N° ou client…" class="sa-input max-w-xs">
    <select name="status" class="sa-select max-w-[10rem]">
        <option value="">Statut</option>
        @foreach($statuses as $k => $v)
            <option value="{{ $k }}" {{ ($filters['status'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    <button class="sa-btn sa-btn-ghost">Filtrer</button>
</form>
<div class="sa-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="sa-table">
            <thead><tr><th>N°</th><th>Client</th><th>Plan</th><th>Émise</th><th>Échéance</th><th>Total</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            @forelse($invoices as $inv)
                <tr>
                    <td class="sa-mono text-xs text-sky-300">{{ $inv->number }}</td>
                    <td>{{ $inv->tenant?->name }}</td>
                    <td class="text-slate-400">{{ $inv->subscription?->plan?->name ?? '—' }}</td>
                    <td class="text-slate-400">{{ optional($inv->issued_on)->format('d/m/Y') }}</td>
                    <td class="text-slate-400">{{ optional($inv->due_on)->format('d/m/Y') }}</td>
                    <td class="font-semibold text-white">{{ number_format($inv->total, 2, ',', ' ') }} {{ $inv->currency }}</td>
                    <td><span class="sa-badge {{ $inv->statusColor() }}">{{ $inv->statusLabel() }}</span></td>
                    <td class="text-right"><a href="{{ route('superadmin.invoices.show', $inv) }}" class="text-xs font-semibold text-sky-400">Ouvrir</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-16 text-center text-slate-500">Aucune facture SaaS</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $invoices->links() }}</div>@endif
</div>
@endsection
