@extends('layouts.superadmin')
@section('title', $invoice->number)
@section('breadcrumb', 'Billing / Factures')
@section('heading', $invoice->number)
@section('actions')
    <a href="{{ route('superadmin.invoices.print', $invoice) }}" class="sa-btn sa-btn-ghost" target="_blank">Imprimer</a>
    <a href="{{ route('superadmin.invoices.pdf', $invoice) }}" class="sa-btn sa-btn-ghost" target="_blank">PDF</a>
    <a href="{{ route('superadmin.invoices.download', $invoice) }}" class="sa-btn sa-btn-ghost">Télécharger</a>
    @if(in_array($invoice->status, ['draft', 'issued'], true))
        <form method="POST" action="{{ route('superadmin.invoices.pay', $invoice) }}">@csrf<button class="sa-btn sa-btn-primary">Encaisser</button></form>
        <form method="POST" action="{{ route('superadmin.invoices.void', $invoice) }}" onsubmit="return confirm('Annuler cette facture ?')">@csrf<button class="sa-btn sa-btn-danger">Void</button></form>
    @endif
@endsection
@section('content')
<div class="mb-4"><span class="sa-badge {{ $invoice->statusColor() }}">{{ $invoice->statusLabel() }}</span></div>
<div class="grid gap-4 xl:grid-cols-3">
    <article class="sa-card space-y-3 text-sm xl:col-span-2">
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-slate-500">Client</dt><dd class="font-semibold text-white">{{ $invoice->tenant?->name }}</dd></div>
            <div><dt class="text-xs text-slate-500">Abonnement</dt><dd>{{ $invoice->subscription?->plan?->name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Émise le</dt><dd>{{ optional($invoice->issued_on)->format('d/m/Y') }}</dd></div>
            <div><dt class="text-xs text-slate-500">Échéance</dt><dd>{{ optional($invoice->due_on)->format('d/m/Y') }}</dd></div>
            <div><dt class="text-xs text-slate-500">Payée le</dt><dd>{{ optional($invoice->paid_at)->format('d/m/Y H:i') ?: '—' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Paiement</dt><dd class="sa-mono text-xs">{{ $invoice->payment?->number ?? '—' }}</dd></div>
        </dl>
        <table class="sa-table mt-4">
            <thead><tr><th>Ligne</th><th class="text-right">Montant</th></tr></thead>
            <tbody>
            @foreach($invoice->line_items ?? [] as $line)
                <tr>
                    <td>{{ $line['label'] ?? 'Ligne' }}</td>
                    <td class="text-right">{{ number_format($line['amount'] ?? 0, 2, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="mt-4 ml-auto w-56 space-y-1 text-sm">
            <div class="flex justify-between text-slate-400"><span>Sous-total</span><span>{{ number_format($invoice->subtotal, 2, ',', ' ') }}</span></div>
            <div class="flex justify-between text-slate-400"><span>TVA</span><span>{{ number_format($invoice->tax, 2, ',', ' ') }}</span></div>
            <div class="flex justify-between border-t border-white/10 pt-2 text-lg font-bold text-white"><span>Total</span><span>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency }}</span></div>
        </div>
    </article>
    <aside class="sa-card text-sm">
        <h2 class="mb-3 text-sm font-bold text-white">Actions</h2>
        <p class="text-xs text-slate-500 mb-3">Paiement via la passerelle de l’abonnement ({{ $invoice->subscription?->provider ?? 'manual' }}).</p>
        <a href="{{ route('superadmin.subscriptions.show', $invoice->subscription) }}" class="sa-btn sa-btn-ghost w-full mb-2 {{ $invoice->subscription ? '' : 'pointer-events-none opacity-40' }}">Voir l’abonnement</a>
        <a href="{{ route('superadmin.invoices.index') }}" class="sa-btn sa-btn-ghost w-full">Historique</a>
    </aside>
</div>
@endsection
