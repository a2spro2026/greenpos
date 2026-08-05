@php
    $items = [
        ['route' => 'pos.dashboard', 'label' => 'Dashboard', 'match' => 'pos.dashboard'],
        ['route' => 'pos.terminal', 'label' => 'Caisse', 'match' => 'pos.terminal'],
        ['route' => 'pos.tickets.index', 'label' => 'Tickets', 'match' => 'pos.tickets.*'],
        ['route' => 'pos.sessions.index', 'label' => 'Sessions', 'match' => 'pos.sessions.*'],
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
