@php
    $items = [
        ['route' => 'pos.dashboard', 'label' => 'Tableau de bord', 'match' => 'pos.dashboard'],
        ['route' => 'pos.terminal', 'label' => 'Caisse', 'match' => 'pos.terminal'],
        ['route' => 'pos.tickets.index', 'label' => 'Tickets enregistrés', 'match' => 'pos.tickets.*'],
        ['route' => 'pos.sessions.index', 'label' => 'Ouvertures / clôtures', 'match' => 'pos.sessions.*'],
        ['route' => 'pos.journal', 'label' => 'Journal de caisse', 'match' => 'pos.journal*'],
        ['route' => 'pos.collections', 'label' => 'À encaisser', 'match' => 'pos.collections'],
        ['route' => 'pos.sync-conflicts.index', 'label' => 'Conflits synchro', 'match' => 'pos.sync-conflicts.*'],
        ['route' => 'pos.printers.index', 'label' => 'Imprimantes', 'match' => 'pos.printers.*'],
    ];
@endphp
<nav class="mb-6 flex w-full max-w-full min-w-0 gap-2 overflow-x-auto pb-1">
    @foreach($items as $item)
        <a href="{{ route($item['route']) }}"
           class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs($item['match']) ? 'bg-gp-primary text-white shadow-sm' : 'bg-white text-gp-muted ring-1 ring-gp-border hover:text-gp-text dark:bg-white/5 dark:ring-white/10 dark:hover:text-white' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
