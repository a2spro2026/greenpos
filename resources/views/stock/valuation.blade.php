@extends('layouts.app')

@section('title', 'Valorisation stock')
@section('breadcrumb', 'Catalogue / Stock / Valorisation')
@section('heading', 'Valorisation')
@section('subtitle', 'Valeur du stock au coût d’achat, par catégorie et boutique.')

@section('content')
    @include('stock._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article class="gp-kpi xl:col-span-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Valeur totale</p>
            <p class="mt-2 text-3xl font-bold text-gp-primary">{{ number_format($total, 2, ',', ' ') }} <span class="text-base text-gp-muted">MAD</span></p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Catégories</p>
            <p class="mt-2 text-3xl font-bold">{{ $byCategory->count() }}</p>
        </article>
        <article class="gp-kpi">
            <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Boutiques</p>
            <p class="mt-2 text-3xl font-bold">{{ $byStore->count() }}</p>
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Valeur par catégorie</h2>
            <canvas id="valuation-category-chart" height="160"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Valeur par boutique</h2>
            <canvas id="valuation-store-chart" height="160"></canvas>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Par catégorie</h2></div>
            @if($byCategory->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune valorisation disponible.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-gp-muted dark:bg-white/5"><tr><th class="px-4 py-3 text-left">Catégorie</th><th class="px-4 py-3 text-right">Qté</th><th class="px-4 py-3 text-right">Valeur</th></tr></thead>
                        <tbody class="divide-y divide-gp-border dark:divide-white/10">
                            @foreach($byCategory as $row)
                                <tr>
                                    <td class="px-4 py-3 font-semibold">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format($row['qty'], 2, ',', ' ') }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ number_format($row['value'], 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>

        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Top produits</h2></div>
            @if($topProducts->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucun produit valorisé.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-gp-muted dark:bg-white/5"><tr><th class="px-4 py-3 text-left">Produit</th><th class="px-4 py-3 text-left">Boutique</th><th class="px-4 py-3 text-right">Valeur</th></tr></thead>
                        <tbody class="divide-y divide-gp-border dark:divide-white/10">
                            @foreach($topProducts as $level)
                                <tr>
                                    <td class="px-4 py-3 font-semibold">{{ $level->product?->name }}</td>
                                    <td class="px-4 py-3">{{ $level->store?->name }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ number_format($level->valuation(), 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const byCategory = @json($byCategory);
        const byStore = @json($byStore);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ef4444', '#14b8a6', '#64748b'];

        new Chart(document.getElementById('valuation-category-chart'), {
            type: 'doughnut',
            data: {
                labels: byCategory.map(i => i.name),
                datasets: [{ data: byCategory.map(i => i.value), backgroundColor: colors, borderWidth: 0 }]
            },
            options: { plugins: { legend: { position: 'bottom', labels: { color: tick } } } }
        });

        new Chart(document.getElementById('valuation-store-chart'), {
            type: 'bar',
            data: {
                labels: byStore.map(i => i.name),
                datasets: [{ label: 'Valeur', data: byStore.map(i => i.value), backgroundColor: '#16a34a', borderRadius: 10 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
    </script>
@endsection
