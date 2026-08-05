@extends('layouts.app')

@section('title', 'Statistiques achats')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Statistiques Achats')
@section('subtitle', 'Évolution et répartition des dépenses.')

@section('content')
    @include('purchases._nav')

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card xl:col-span-2">
            <h2 class="mb-4 text-sm font-bold">Évolution des achats (12 mois)</h2>
            <canvas id="purchases-evolution-chart" height="110"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Dépenses par fournisseur</h2>
            <canvas id="purchases-supplier-stats" height="160"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Dépenses par catégorie</h2>
            <canvas id="purchases-category-stats" height="160"></canvas>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Par boutique</h2></div>
            @if($byStore->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Pas encore de données.</div>
            @else
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($byStore as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ number_format($row['total'], 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Par fournisseur</h2></div>
            @if($bySupplier->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Pas encore de données.</div>
            @else
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($bySupplier as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ number_format($row['total'], 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const evolution = @json($evolution);
        const bySupplier = @json($bySupplier);
        const byCategory = @json($byCategory);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#0ea5e9','#f59e0b','#8b5cf6','#ef4444','#14b8a6','#64748b'];

        new Chart(document.getElementById('purchases-evolution-chart'), {
            type: 'line',
            data: { labels: evolution.map(i => i.label), datasets: [{ data: evolution.map(i => i.total), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
        new Chart(document.getElementById('purchases-supplier-stats'), {
            type: 'doughnut',
            data: { labels: bySupplier.map(i => i.name), datasets: [{ data: bySupplier.map(i => i.total), backgroundColor: colors, borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom', labels: { color: tick } } } }
        });
        new Chart(document.getElementById('purchases-category-stats'), {
            type: 'bar',
            data: { labels: byCategory.map(i => i.name), datasets: [{ data: byCategory.map(i => i.total), backgroundColor: '#0ea5e9', borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
    </script>
@endsection
