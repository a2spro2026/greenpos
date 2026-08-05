@extends('layouts.app')

@section('title', 'Alertes stock')
@section('breadcrumb', 'Catalogue / Stock / Alertes')
@section('heading', 'Alertes stock')
@section('subtitle', 'Ruptures, stocks faibles et surstocks à traiter.')

@section('actions')
    @can('stock.export')
        <a href="{{ route('stock.alerts.export', request()->query()) }}" class="gp-btn-secondary">Exporter</a>
    @endcan
@endsection

@section('content')
    @include('stock._nav')

    <section class="mb-4 grid gap-3 sm:grid-cols-3">
        <a href="{{ route('stock.alerts', ['type' => 'low'] + request()->except('type', 'page')) }}" class="gp-kpi transition hover:-translate-y-0.5 {{ $type === 'low' ? 'ring-2 ring-gp-primary' : '' }}">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Faibles</p>
            <p class="mt-2 text-3xl font-bold text-gp-warning">{{ $counts['low'] }}</p>
        </a>
        <a href="{{ route('stock.alerts', ['type' => 'out'] + request()->except('type', 'page')) }}" class="gp-kpi transition hover:-translate-y-0.5 {{ $type === 'out' ? 'ring-2 ring-gp-primary' : '' }}">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Ruptures</p>
            <p class="mt-2 text-3xl font-bold text-rose-600">{{ $counts['out'] }}</p>
        </a>
        <a href="{{ route('stock.alerts', ['type' => 'over'] + request()->except('type', 'page')) }}" class="gp-kpi transition hover:-translate-y-0.5 {{ $type === 'over' ? 'ring-2 ring-gp-primary' : '' }}">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Surstocks</p>
            <p class="mt-2 text-3xl font-bold text-sky-600">{{ $counts['over'] }}</p>
        </a>
    </section>

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 sm:grid-cols-4">
            <input type="hidden" name="type" value="{{ $type }}">
            <select name="store_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Toutes boutiques</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="category_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Catégories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="gp-btn-primary sm:col-span-2">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($alerts->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucune alerte</p>
                <p class="mt-2 text-sm text-gp-muted">Tout est sous contrôle pour ce filtre.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Produit</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Qté</th>
                            <th class="px-4 py-3">Min / Max</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Correction rapide</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($alerts as $level)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ $level->product?->name }}</p>
                                    <p class="font-mono text-xs text-gp-muted">{{ $level->product?->sku }}</p>
                                </td>
                                <td class="px-4 py-3">{{ $level->store?->name }}</td>
                                <td class="px-4 py-3 font-bold">{{ number_format($level->quantity, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ number_format($level->min_quantity, 3, ',', ' ') }} / {{ $level->max_quantity !== null ? number_format($level->max_quantity, 3, ',', ' ') : '—' }}</td>
                                <td class="px-4 py-3"><span class="gp-badge">{{ $level->statusLabel() }}</span></td>
                                <td class="px-4 py-3">
                                    @can('stock.move')
                                        <a href="{{ route('stock.movements.create', ['product_id' => $level->product_id, 'store_id' => $level->store_id, 'type' => $type === 'over' ? 'out' : 'in']) }}" class="text-xs font-semibold text-gp-primary hover:underline">
                                            {{ $type === 'over' ? 'Sortie' : 'Réappro' }}
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $alerts->links() }}</div>
        @endif
    </section>
@endsection
