@extends('layouts.app')

@section('title', 'Pertes stock')
@section('breadcrumb', 'Opérations')
@section('heading', 'Pertes & cassures')
@section('subtitle', 'Pertes, péremptions, vols — sortie de stock.')

@section('actions')
    <a href="{{ route('ops.losses.create') }}" class="gp-btn-primary">Nouvelle perte</a>
@endsection

@section('content')
    @include('production._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N°, responsable…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="loss_type" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Type</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['loss_type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="store_id" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Boutique</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($losses->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucune perte</p>
                <a href="{{ route('ops.losses.create') }}" class="gp-btn-primary mt-5">Déclarer une perte</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">N°</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Coût</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($losses as $loss)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $loss->number }}</td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $loss->loss_date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3"><span class="gp-badge {{ $loss->typeColor() }}">{{ $loss->typeLabel() }}</span></td>
                                <td class="px-4 py-3">{{ $loss->store?->name }}</td>
                                <td class="px-4 py-3 font-semibold">{{ number_format($loss->total_cost, 2, ',', ' ') }} {{ $currency }}</td>
                                <td class="px-4 py-3 text-xs font-semibold"><a href="{{ route('ops.losses.show', $loss) }}" class="text-gp-primary hover:underline">Voir</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3">{{ $losses->links() }}</div>
        @endif
    </section>
@endsection
