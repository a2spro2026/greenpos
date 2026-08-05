@extends('layouts.app')

@section('title', 'Statistiques clients')
@section('breadcrumb', 'Relation Client')
@section('heading', 'Statistiques Clients')
@section('subtitle', 'Top clients, CA, évolution et géographie.')

@section('content')
    @include('customers._nav')

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card xl:col-span-2">
            <h2 class="mb-4 text-sm font-bold">Évolution des clients</h2>
            <canvas id="customers-stats-evolution" height="110"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">CA par client (Top)</h2>
            <canvas id="customers-stats-revenue" height="160"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Répartition géographique</h2>
            <canvas id="customers-stats-geo" height="160"></canvas>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Top clients</h2></div>
            @if($top->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Pas encore de CA client.</div>
            @else
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($top as $i => $customer)
                            <tr>
                                <td class="px-4 py-3 text-gp-muted">#{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('customers.show', $customer) }}" class="hover:text-gp-primary">{{ $customer->displayName() }}</a></td>
                                <td class="px-4 py-3 text-right font-bold">{{ number_format($customer->lifetime_revenue, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Clients inactifs</h2></div>
            @if($inactive->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucun client inactif.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($inactive as $customer)
                        <li class="px-5 py-3 text-sm">
                            <a href="{{ route('customers.show', $customer) }}" class="font-semibold hover:text-gp-primary">{{ $customer->displayName() }}</a>
                            <p class="text-xs text-gp-muted">{{ $customer->city ?: '—' }} · {{ $customer->code }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const evolution = @json($evolution);
        const byRevenue = @json($byRevenue);
        const byCountry = @json($byCountry);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const colors = ['#16a34a','#0ea5e9','#f59e0b','#8b5cf6','#ef4444','#14b8a6','#64748b'];

        new Chart(document.getElementById('customers-stats-evolution'), {
            type: 'line',
            data: { labels: evolution.map(i => i.label), datasets: [{ data: evolution.map(i => i.count), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, precision: 0 } } }
        });
        new Chart(document.getElementById('customers-stats-revenue'), {
            type: 'bar',
            data: { labels: byRevenue.map(i => i.name), datasets: [{ data: byRevenue.map(i => i.total), backgroundColor: '#0ea5e9', borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
        new Chart(document.getElementById('customers-stats-geo'), {
            type: 'doughnut',
            data: { labels: byCountry.map(i => i.name), datasets: [{ data: byCountry.map(i => i.count), backgroundColor: colors, borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom', labels: { color: tick } } } }
        });
    </script>
@endsection
