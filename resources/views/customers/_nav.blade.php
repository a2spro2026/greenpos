@php
    $items = [
        ['route' => 'customers.dashboard', 'label' => 'Dashboard', 'match' => 'customers.dashboard'],
        ['route' => 'customers.index', 'label' => 'Clients', 'match' => 'customers.index'],
        ['route' => 'customers.stats', 'label' => 'Statistiques', 'match' => 'customers.stats'],
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
