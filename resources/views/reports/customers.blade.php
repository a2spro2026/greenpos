@extends('layouts.app')

@section('title', 'Rapport Clients')
@section('breadcrumb', 'Rapports / Clients')
@section('heading', 'Rapport Clients')
@section('subtitle', 'Analyse de la base clients et du chiffre d\'affaires par client.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('reports.export')
            <a href="{{ route('reports.export', array_merge(request()->only(['from','to','store_id','customer_id']), ['type' => 'customers'])) }}" class="gp-btn-secondary">Exporter CSV</a>
        @endcan
        @can('reports.print')
            <a href="{{ route('reports.print', array_merge(request()->only(['from','to','store_id','customer_id']), ['type' => 'customers'])) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('reports._nav')
    @include('reports._filters', ['action' => route('reports.customers')])

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">CA par client (top 10)</h2>
            <canvas id="chart-revenue" height="180"></canvas>
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Meilleurs clients</h2></div>
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @forelse($bestCustomers->take(10) as $c)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <div>
                            <a href="{{ route('customers.show', $c['customer']) }}" class="font-semibold text-gp-primary hover:underline">{{ $c['customer']?->name ?? '—' }}</a>
                            <p class="text-xs text-gp-muted">{{ $c['count'] }} ventes</p>
                        </div>
                        <span class="font-bold">{{ number_format($c['total'], 2, ',', ' ') }} MAD</span>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-gp-muted">Aucun client.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Nouveaux clients</h2></div>
            @if($newCustomers->isEmpty())
                <div class="px-6 py-8 text-center text-sm text-gp-muted">Aucun nouveau client.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($newCustomers as $c)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <a href="{{ route('customers.show', $c) }}" class="font-semibold text-gp-primary hover:underline">{{ $c->name }}</a>
                                <p class="text-xs text-gp-muted">{{ $c->email ?? $c->phone ?? '—' }}</p>
                            </div>
                            <span class="text-xs text-gp-muted">{{ $c->created_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Clients inactifs</h2></div>
            @if($inactiveCustomers->isEmpty())
                <div class="px-6 py-8 text-center text-sm text-gp-muted">Tous les clients sont actifs.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($inactiveCustomers as $c)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <a href="{{ route('customers.show', $c) }}" class="font-semibold text-gp-primary hover:underline">{{ $c->name }}</a>
                                <p class="text-xs text-gp-muted">Dernier achat : {{ $c->last_purchase_at?->format('d/m/Y') ?? 'Jamais' }}</p>
                            </div>
                            <span class="inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-800">Inactif</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const tick = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
        const data = @json($revenueByCustomer->take(10));
        new Chart(document.getElementById('chart-revenue'), {
            type:'bar',
            data:{ labels:data.map(c=>c.customer?.name ?? '—'), datasets:[{ data:data.map(c=>c.total), backgroundColor:'rgba(22,163,74,.65)', borderRadius:4 }] },
            options:{ plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ color:tick, maxRotation:45 }}, y:{ ticks:{ color:tick }}} }
        });
    </script>
@endsection
