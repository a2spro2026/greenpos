@php
    $items = [
        ['route' => 'stock.dashboard', 'label' => 'Dashboard', 'match' => 'stock.dashboard'],
        ['route' => 'stock.levels', 'label' => 'Stocks', 'match' => 'stock.levels*'],
        ['route' => 'stock.movements.index', 'label' => 'Mouvements', 'match' => 'stock.movements.*'],
        ['route' => 'stock.inventories.index', 'label' => 'Inventaires', 'match' => 'stock.inventories.*'],
        ['route' => 'stock.alerts', 'label' => 'Alertes', 'match' => 'stock.alerts*'],
        ['route' => 'stock.valuation', 'label' => 'Valorisation', 'match' => 'stock.valuation'],
    ];
@endphp
<nav class="mb-6 flex gap-2 overflow-x-auto pb-1">
    @foreach($items as $item)
        <a href="{{ route($item['route']) }}"
           class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs($item['match']) ? 'bg-gp-primary text-white shadow-sm' : 'bg-white text-gp-muted ring-1 ring-gp-border hover:text-gp-text dark:bg-white/5 dark:ring-white/10 dark:hover:text-white' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
