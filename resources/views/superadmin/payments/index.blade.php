@extends('layouts.superadmin')
@section('title', 'Paiements SaaS')
@section('breadcrumb', 'Platform / Billing')
@section('heading', 'Paiements & factures SaaS')
@section('actions')
    <a href="{{ route('superadmin.billing.gateways') }}" class="sa-btn sa-btn-ghost">Passerelles</a>
    <a href="{{ route('superadmin.invoices.index') }}" class="sa-btn sa-btn-ghost">Factures</a>
    <a href="{{ route('superadmin.payments.create') }}" class="sa-btn sa-btn-primary">Enregistrer un paiement</a>
@endsection
@section('content')
<div class="mb-4 grid gap-3 sm:grid-cols-4">
    @foreach(['stripe' => 'Stripe', 'paypal' => 'PayPal', 'cmi' => 'CMI', 'manual' => 'Manuel'] as $k => $label)
        <a href="{{ route('superadmin.billing.gateways') }}" class="sa-card !py-3 text-center transition hover:border-sky-500/30">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-200">Connecteur prêt</p>
        </a>
    @endforeach
</div>
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <select name="provider" class="sa-select max-w-[10rem]">
        <option value="">Provider</option>
        @foreach($providers as $k => $v)<option value="{{ $k }}" {{ ($filters['provider'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
    </select>
    <select name="status" class="sa-select max-w-[10rem]">
        <option value="">Statut</option>
        @foreach($statuses as $k => $v)<option value="{{ $k }}" {{ ($filters['status'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
    </select>
    <button class="sa-btn sa-btn-ghost">Filtrer</button>
</form>
<div class="grid gap-4 xl:grid-cols-3">
    <div class="sa-card overflow-hidden p-0 xl:col-span-2">
        <div class="overflow-x-auto">
            <table class="sa-table">
                <thead><tr><th>Réf</th><th>Client</th><th>Provider</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td class="sa-mono text-xs">{{ $p->number }}</td>
                        <td>{{ $p->tenant?->name }}</td>
                        <td>{{ $p->providerLabel() }}</td>
                        <td class="font-semibold text-emerald-300">{{ number_format($p->amount, 2, ',', ' ') }} {{ $p->currency }}</td>
                        <td>{{ $p->statusLabel() }}</td>
                        <td class="text-slate-400">{{ optional($p->paid_at)->format('d/m/Y') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-16 text-center text-slate-500">Aucun paiement</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $payments->links() }}</div>@endif
    </div>
    <aside class="sa-card">
        <h2 class="mb-3 text-sm font-bold text-white">Dernières factures SaaS</h2>
        <ul class="space-y-3 text-sm">
            @forelse($invoices as $inv)
                <li class="rounded-lg border border-white/5 px-3 py-2">
                    <a href="{{ route('superadmin.invoices.show', $inv) }}" class="sa-mono text-xs text-sky-300 hover:underline">{{ $inv->number }}</a>
                    <p class="mt-1">{{ $inv->tenant?->name }}</p>
                    <p class="text-xs text-slate-500">{{ number_format($inv->total, 2, ',', ' ') }} · {{ $inv->statusLabel() }}</p>
                </li>
            @empty
                <li class="text-slate-500">Aucune facture</li>
            @endforelse
        </ul>
        <a href="{{ route('superadmin.invoices.index') }}" class="mt-4 inline-block text-xs font-semibold text-sky-400">Voir l’historique →</a>
    </aside>
</div>
@endsection
