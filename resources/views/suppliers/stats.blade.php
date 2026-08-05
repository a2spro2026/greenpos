@extends('layouts.app')

@section('title', 'Statistiques fournisseurs')
@section('breadcrumb', 'Approvisionnement / Fournisseurs')
@section('heading', 'Statistiques Fournisseurs')
@section('subtitle', 'Classements, dépenses et répartition géographique.')

@section('content')
    @include('suppliers._nav')

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card xl:col-span-2">
            <h2 class="mb-4 text-sm font-bold">Évolution des achats fournisseurs</h2>
            <canvas id="suppliers-stats-evolution" height="110"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Dépenses par fournisseur</h2>
            <canvas id="suppliers-stats-spend" height="160"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Répartition géographique</h2>
            <canvas id="suppliers-stats-geo" height="160"></canvas>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Top fournisseurs</h2></div>
            @if($ranking->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Pas encore de dépenses.</div>
            @else
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($ranking as $i => $row)
                            <tr>
                                <td class="px-4 py-3 text-gp-muted">#{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $row['orders'] }} cmd</td>
                                <td class="px-4 py-3 text-right font-bold">{{ number_format($row['total'], 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Par pays</h2></div>
            @if($byCountry->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune donnée géographique.</div>
            @else
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($byCountry as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ $row['count'] }}</td>
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
        const spend = @json($spend);
        const byCountry = @json($byCountry);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#0ea5e9','#f59e0b','#8b5cf6','#ef4444','#14b8a6','#64748b'];

        new Chart(document.getElementById('suppliers-stats-evolution'), {
            type: 'line',
            data: { labels: evolution.map(i => i.label), datasets: [{ data: evolution.map(i => i.total), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
        new Chart(document.getElementById('suppliers-stats-spend'), {
            type: 'bar',
            data: { labels: spend.map(i => i.name), datasets: [{ data: spend.map(i => i.total), backgroundColor: '#0ea5e9', borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
        new Chart(document.getElementById('suppliers-stats-geo'), {
            type: 'doughnut',
            data: { labels: byCountry.map(i => i.name), datasets: [{ data: byCountry.map(i => i.count), backgroundColor: colors, borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom', labels: { color: tick } } } }
        });
    </script>
@endsection
