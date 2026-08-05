@extends('layouts.app')

@section('title', 'Inventaires')
@section('breadcrumb', 'Catalogue / Stock / Inventaires')
@section('heading', 'Inventaires')
@section('subtitle', 'Compter, corriger et valider les écarts de stock.')

@section('actions')
    @can('stock.inventory')
        <a href="{{ route('stock.inventories.create') }}" class="gp-btn-primary">Nouvel inventaire</a>
    @endcan
@endsection

@section('content')
    @include('stock._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="gp-card overflow-hidden p-0">
        @if($inventories->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucun inventaire</p>
                <p class="mt-2 text-sm text-gp-muted">Lancez un inventaire pour fiabiliser les quantités boutique.</p>
                <a href="{{ route('stock.inventories.create') }}" class="gp-btn-primary mt-5">Démarrer un inventaire</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Progression</th>
                            <th class="px-4 py-3">Créé par</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($inventories as $inventory)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('stock.inventories.show', $inventory) }}" class="hover:text-gp-primary">{{ $inventory->name }}</a></td>
                                <td class="px-4 py-3">{{ $inventory->store?->name }}</td>
                                <td class="px-4 py-3"><span class="gp-badge">{{ $inventory->statusLabel() }}</span></td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $inventory->counted_lines_count }} / {{ $inventory->lines_count }}</td>
                                <td class="px-4 py-3">{{ $inventory->creator?->name ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $inventory->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $inventories->links() }}</div>
        @endif
    </section>
@endsection
