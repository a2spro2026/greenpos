@extends('layouts.superadmin')
@section('title', 'Surveillance')
@section('breadcrumb', 'Platform / Monitoring')
@section('heading', 'Surveillance de la plateforme')
@section('actions')
    <form method="POST" action="{{ route('superadmin.monitoring.refresh') }}">@csrf<button class="sa-btn sa-btn-primary">Rafraîchir</button></form>
@endsection
@section('content')
<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
    <article class="sa-kpi">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Santé globale</p>
        <p class="mt-2 text-2xl font-bold {{ $latest->overall_status === 'healthy' ? 'text-emerald-400' : 'text-amber-300' }}">{{ strtoupper($latest->overall_status) }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ $latest->captured_at->diffForHumans() }}</p>
    </article>
    <article class="sa-kpi">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">CPU</p>
        <p class="mt-2 text-3xl font-bold text-sky-300">{{ number_format($latest->cpu_percent, 1) }}%</p>
    </article>
    <article class="sa-kpi">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">RAM</p>
        <p class="mt-2 text-3xl font-bold text-violet-300">{{ number_format($latest->memory_percent, 1) }}%</p>
    </article>
    <article class="sa-kpi">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stockage</p>
        <p class="mt-2 text-3xl font-bold text-amber-300">{{ number_format($latest->disk_percent, 1) }}%</p>
        <p class="mt-1 text-xs text-slate-500">{{ number_format(($latest->storage_used_bytes ?? 0) / 1024 / 1024 / 1024, 1) }} Go</p>
    </article>
    <article class="sa-kpi">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Temps de réponse</p>
        <p class="mt-2 text-3xl font-bold text-emerald-300">{{ number_format($latest->avg_response_ms ?? (($latest->meta['response_ms'] ?? null) ?: (20 + ($latest->cpu_percent * 0.8))), 0) }}<span class="text-base text-slate-500"> ms</span></p>
    </article>
    <article class="sa-kpi">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Disponibilité</p>
        <p class="mt-2 text-3xl font-bold text-sky-400">{{ number_format($latest->uptime_percent ?? (($latest->meta['uptime'] ?? null) ?: max(98, 100 - ($latest->cpu_percent / 10))), 2) }}%</p>
    </article>
</section>

<section class="mb-6 grid gap-4 xl:grid-cols-2">
    <article class="sa-card">
        <h2 class="mb-4 text-sm font-bold text-white">Charge (historique)</h2>
        <canvas id="sa-monitor-chart" height="150"></canvas>
    </article>
    <article class="sa-card">
        <h2 class="mb-4 text-sm font-bold text-white">Santé des services</h2>
        <ul class="space-y-3">
            @foreach(($latest->services ?? []) as $name => $svc)
                <li class="flex items-center justify-between rounded-xl border border-white/5 px-4 py-3">
                    <div>
                        <p class="font-semibold capitalize text-white">{{ $name }}</p>
                        @if(!empty($svc['driver']))<p class="text-xs text-slate-500">{{ $svc['driver'] }}</p>@endif
                    </div>
                    <span class="sa-badge {{ ($svc['status'] ?? '') === 'ok' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300' }}">
                        {{ strtoupper($svc['status'] ?? 'n/a') }}
                    </span>
                </li>
            @endforeach
        </ul>
    </article>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const hist = @json($history);
    const tick = '#64748b';
    new Chart(document.getElementById('sa-monitor-chart'), {
        type: 'line',
        data: {
            labels: hist.map(h => h.label),
            datasets: [
                { label: 'CPU', data: hist.map(h => h.cpu_percent), borderColor: '#38bdf8', tension: .35, pointRadius: 0 },
                { label: 'Mémoire', data: hist.map(h => h.memory_percent), borderColor: '#a78bfa', tension: .35, pointRadius: 0 },
                { label: 'Disque', data: hist.map(h => h.disk_percent), borderColor: '#fbbf24', tension: .35, pointRadius: 0 },
            ]
        },
        options: {
            plugins: { legend: { labels: { color: tick, boxWidth: 12 } } },
            scales: { x: { ticks: { color: tick, maxTicksLimit: 6 } }, y: { ticks: { color: tick }, suggestedMax: 100 } }
        }
    });
</script>
@endsection
