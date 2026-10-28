@php
    $items = [
        ['route' => 'sales.dashboard', 'label' => 'Tableau de bord', 'match' => 'sales.dashboard'],
        ['route' => 'sales.index', 'label' => 'Commandes & ventes', 'match' => 'sales.index'],
        ['route' => 'sales.create', 'label' => 'Nouvelle commande', 'match' => 'sales.create'],
        ['route' => 'pos.tickets.index', 'label' => 'Tickets de caisse (POS)', 'match' => 'pos.tickets.*'],
    ];
    $posMonth = \App\Models\PosSale::query()->where('company_id', \App\Support\Workspace::company()?->id)
        ->where('status', 'completed')->whereNotIn('id', \App\Models\Sale::query()->where('company_id', \App\Support\Workspace::company()?->id)->whereNotNull('pos_sale_id')->select('pos_sale_id'))
        ->where('completed_at', '>=', now()->startOfMonth())->selectRaw('COUNT(*) as n, COALESCE(SUM(total_ttc),0) as t')->first();
@endphp
<nav class="mb-6 flex gap-2 overflow-x-auto pb-1">
    @foreach($items as $item)
        <a href="{{ route($item['route']) }}"
           class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs($item['match']) ? 'bg-gp-primary text-white shadow-sm' : 'bg-white text-gp-muted ring-1 ring-gp-border hover:text-gp-text dark:bg-white/5 dark:ring-white/10 dark:hover:text-white' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
@if(($posMonth->n ?? 0) > 0 && ! request()->routeIs('sales.create'))
    <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-2 text-sm text-sky-900 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-100">
        Ce mois : {{ $posMonth->n }} ticket(s) de caisse pour {{ number_format((float) $posMonth->t, 2, ',', ' ') }} {{ \App\Support\Workspace::company()?->currency ?? 'MAD' }} — ils sont listés dans
        <a class="font-semibold underline" href="{{ route('pos.tickets.index') }}">Tickets de caisse (POS)</a> et comptés dans les <a class="font-semibold underline" href="{{ route('reports.dashboard') }}">rapports</a>. Cette page liste les commandes et ventes saisies au back-office.
    </div>
@endif
