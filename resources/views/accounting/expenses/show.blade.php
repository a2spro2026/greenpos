@extends('layouts.app')

@section('title', $expense->label)
@section('breadcrumb', 'Comptabilité / Dépenses')
@section('heading', $expense->label)
@section('subtitle', 'Dépense du '.$expense->expense_date?->format('d/m/Y'))

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('accounting.expenses.edit', $expense) }}" class="gp-btn-secondary">Modifier</a>
        <form method="post" action="{{ route('accounting.expenses.destroy', $expense) }}" onsubmit="return confirm('Archiver cette dépense ?')">
            @csrf @method('DELETE')
            <button class="gp-btn-secondary text-rose-600">Archiver</button>
        </form>
    </div>
@endsection

@section('content')
    @include('accounting._nav')
    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="gp-card">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Montant</dt><dd class="mt-1 text-lg font-bold">{{ number_format($expense->amount, 2, ',', ' ') }} {{ $expense->currency }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Type</dt><dd class="mt-1 font-semibold">{{ $expense->typeLabel() }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Catégorie</dt><dd class="mt-1">{{ $expense->categoryLabel() }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Boutique</dt><dd class="mt-1">{{ $expense->store?->name ?: '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Fournisseur</dt><dd class="mt-1">{{ $expense->supplier?->name ?: '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Paiement</dt><dd class="mt-1">{{ $expense->payment_method ?: '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase text-gp-muted">Récurrence</dt><dd class="mt-1">{{ $expense->is_recurring ? $expense->recurrenceLabel() : 'Non' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gp-muted">Notes</dt><dd class="mt-1 whitespace-pre-wrap">{{ $expense->notes ?: '—' }}</dd></div>
        </dl>
    </section>
@endsection
