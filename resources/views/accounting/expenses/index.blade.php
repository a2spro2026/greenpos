@extends('layouts.app')

@section('title', 'Dépenses')
@section('breadcrumb', 'Comptabilité')
@section('heading', 'Dépenses')
@section('subtitle', 'Charges fixes et variables — suivi et journal de caisse.')

@section('actions')
    <a href="{{ route('accounting.expenses.create') }}" class="gp-btn-primary">Nouvelle dépense</a>
@endsection

@section('content')
    @include('accounting._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Total</p><p class="mt-1 text-xl font-bold">{{ number_format($stats['total'], 2, ',', ' ') }} {{ $currency }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Ce mois</p><p class="mt-1 text-xl font-bold">{{ number_format($stats['month'], 2, ',', ' ') }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Fixes</p><p class="mt-1 text-xl font-bold">{{ number_format($stats['fixed'], 2, ',', ' ') }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Variables</p><p class="mt-1 text-xl font-bold">{{ number_format($stats['variable'], 2, ',', ' ') }}</p></div>
    </div>

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Libellé…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="category" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Catégories</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="expense_type" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Types</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['expense_type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($expenses->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucune dépense</p>
                <a href="{{ route('accounting.expenses.create') }}" class="gp-btn-primary mt-5">Ajouter la première</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Libellé</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Montant</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($expenses as $expense)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $expense->expense_date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('accounting.expenses.show', $expense) }}" class="hover:text-gp-primary">{{ $expense->label }}</a></td>
                                <td class="px-4 py-3">{{ $expense->categoryLabel() }}</td>
                                <td class="px-4 py-3">{{ $expense->typeLabel() }}</td>
                                <td class="px-4 py-3 font-semibold">{{ number_format($expense->amount, 2, ',', ' ') }} {{ $expense->currency }}</td>
                                <td class="px-4 py-3 text-xs font-semibold">
                                    <a href="{{ route('accounting.expenses.show', $expense) }}" class="text-gp-primary hover:underline">Voir</a>
                                    <a href="{{ route('accounting.expenses.edit', $expense) }}" class="ml-2 text-gp-muted hover:underline">Modifier</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $expenses->links() }}</div>
        @endif
    </section>
@endsection
