@extends('layouts.app')

@section('title', 'Production')
@section('breadcrumb', 'Opérations')
@section('heading', 'Production')
@section('subtitle', 'Fabrication — brouillons et validations stock.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('ops.production.bom') }}" class="gp-btn-secondary">Nomenclatures</a>
        <a href="{{ route('ops.production.create') }}" class="gp-btn-primary">Nouvelle production</a>
    </div>
@endsection

@section('content')
    @include('production._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-5">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N° ou produit…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="status" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Statut</option>
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
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($productions->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucune production</p>
                <a href="{{ route('ops.production.create') }}" class="gp-btn-primary mt-5">Créer un brouillon</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">N°</th>
                            <th class="px-4 py-3">Produit</th>
                            <th class="px-4 py-3">Qté</th>
                            <th class="px-4 py-3">Coût</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($productions as $item)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $item->number }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $item->product?->name }}</td>
                                <td class="px-4 py-3">{{ number_format($item->quantity, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3">{{ number_format($item->total_cost, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3"><span class="gp-badge {{ $item->statusColor() }}">{{ $item->statusLabel() }}</span></td>
                                <td class="px-4 py-3 text-xs font-semibold">
                                    <a href="{{ route('ops.production.show', $item) }}" class="text-gp-primary hover:underline">Voir</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3">{{ $productions->links() }}</div>
        @endif
    </section>
@endsection
