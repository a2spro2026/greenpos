@extends('layouts.app')

@section('title', 'Stats paie')
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Statistiques paie')
@section('subtitle', 'Évolution mensuelle et top collaborateurs pour '.$year.'.')

@section('actions')
    <a href="{{ route('hr.payroll.index') }}" class="gp-btn-secondary">Retour paie</a>
@endsection

@section('content')
    @include('hr._nav')

    <section class="gp-card mb-4">
        <form method="get" class="flex flex-wrap items-end gap-3">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Année</span>
                <input type="number" name="year" value="{{ $year }}" class="rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <button class="gp-btn-primary">Actualiser</button>
        </form>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Net mensuel {{ $year }}</h2>
            <canvas id="payroll-month-chart" height="140"></canvas>
        </article>
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Top net cumulé</h2>
            <canvas id="payroll-top-chart" height="140"></canvas>
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const byMonth = @json($byMonth);
        const top = @json($top);
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        new Chart(document.getElementById('payroll-month-chart'), {
            type: 'line',
            data: { labels: byMonth.map(i => i.label), datasets: [{ data: byMonth.map(i => i.net), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', fill: true, tension: .35 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
        new Chart(document.getElementById('payroll-top-chart'), {
            type: 'bar',
            data: { labels: top.map(i => i.name), datasets: [{ data: top.map(i => i.net), backgroundColor: '#0ea5e9', borderRadius: 8 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick } } } }
        });
    </script>
@endsection
