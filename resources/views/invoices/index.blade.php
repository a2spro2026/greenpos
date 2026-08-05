@extends('layouts.app')

@section('title', 'Factures')
@section('breadcrumb', 'Finance / Facturation')
@section('heading', 'Liste des factures')
@section('subtitle', 'Recherche, filtres et export.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('invoices.export')
            <a href="{{ route('invoices.export', request()->query()) }}" class="gp-btn-secondary">Export Excel</a>
        @endcan
        @can('invoices.create')
            <a href="{{ route('invoices.create') }}" class="gp-btn-primary">Nouvelle facture</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('invoices._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-7">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N°, client, référence…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="status" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous statuts</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="store_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Boutique</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="customer_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Client</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->displayName() }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($invoices->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gp-primary-soft text-gp-primary">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/></svg>
                </div>
                <p class="text-lg font-bold">Aucune facture</p>
                <p class="mt-2 text-sm text-gp-muted">Créez votre première facture client.</p>
                @can('invoices.create')
                    <a href="{{ route('invoices.create') }}" class="gp-btn-primary mt-5">Créer une facture</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            @foreach([
                                ['key' => 'number', 'label' => 'Numéro'],
                                ['key' => 'customer', 'label' => 'Client'],
                                ['key' => 'invoiced_at', 'label' => 'Date'],
                                ['key' => 'due_at', 'label' => 'Échéance'],
                                ['key' => 'subtotal_ht', 'label' => 'HT'],
                                ['key' => 'tax_total', 'label' => 'TVA'],
                                ['key' => 'total_ttc', 'label' => 'TTC'],
                                ['key' => 'amount_paid', 'label' => 'Payé'],
                                ['key' => 'balance_due', 'label' => 'Reste'],
                                ['key' => 'status', 'label' => 'Statut'],
                                ['key' => 'store', 'label' => 'Boutique'],
                                ['key' => 'actions', 'label' => ''],
                            ] as $col)
                                <th class="px-3 py-3 whitespace-nowrap">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($invoices as $invoice)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-3 py-3 font-semibold whitespace-nowrap"><a href="{{ route('invoices.show', $invoice) }}" class="text-gp-primary hover:underline">{{ $invoice->number }}</a></td>
                                <td class="px-3 py-3">{{ $invoice->customer?->displayName() }}</td>
                                <td class="px-3 py-3 text-gp-muted whitespace-nowrap">{{ optional($invoice->invoiced_at)->format('d/m/Y') }}</td>
                                <td class="px-3 py-3 text-gp-muted whitespace-nowrap">{{ optional($invoice->due_at)->format('d/m/Y') ?: '—' }}</td>
                                <td class="px-3 py-3 text-right">{{ number_format($invoice->subtotal_ht, 2, ',', ' ') }}</td>
                                <td class="px-3 py-3 text-right">{{ number_format($invoice->tax_total, 2, ',', ' ') }}</td>
                                <td class="px-3 py-3 text-right font-bold">{{ number_format($invoice->total_ttc, 2, ',', ' ') }}</td>
                                <td class="px-3 py-3 text-right text-emerald-600">{{ number_format($invoice->amount_paid, 2, ',', ' ') }}</td>
                                <td class="px-3 py-3 text-right {{ $invoice->balance_due > 0 ? 'font-bold text-amber-600' : '' }}">{{ number_format($invoice->balance_due, 2, ',', ' ') }}</td>
                                <td class="px-3 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $invoice->statusColor() }}">{{ $invoice->statusLabel() }}</span></td>
                                <td class="px-3 py-3 text-gp-muted">{{ $invoice->store?->name }}</td>
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-xs font-semibold text-gp-primary hover:underline">Voir</a>
                                    @can('invoices.print')
                                        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="ml-2 text-xs font-semibold text-gp-muted hover:text-gp-primary">Impr.</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($invoices->hasPages())
                <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $invoices->links() }}</div>
            @endif
        @endif
    </section>
@endsection
