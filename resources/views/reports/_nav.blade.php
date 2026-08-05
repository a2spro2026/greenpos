@php
    $items = [
        ['route' => 'reports.dashboard', 'label' => 'Tableau de bord BI', 'match' => 'reports.dashboard'],
        ['route' => 'reports.sales', 'label' => 'Ventes', 'match' => 'reports.sales'],
        ['route' => 'reports.products', 'label' => 'Produits', 'match' => 'reports.products'],
        ['route' => 'reports.customers', 'label' => 'Clients', 'match' => 'reports.customers'],
        ['route' => 'reports.payments', 'label' => 'Paiements', 'match' => 'reports.payments', 'gate' => 'reports.financial'],
        ['route' => 'reports.stock', 'label' => 'Stock', 'match' => 'reports.stock'],
    ];
@endphp
<nav class="mb-6 flex gap-2 overflow-x-auto pb-1">
    @foreach($items as $item)
        @if(!isset($item['gate']) || Gate::check($item['gate']))
            <a href="{{ route($item['route']) }}"
               class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs($item['match']) ? 'bg-gp-primary text-white shadow-sm' : 'bg-white text-gp-muted ring-1 ring-gp-border hover:text-gp-text dark:bg-white/5 dark:ring-white/10 dark:hover:text-white' }}">
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
