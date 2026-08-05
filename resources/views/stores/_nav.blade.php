@php
    $items = [
        ['route' => 'stores.dashboard', 'label' => 'Dashboard', 'match' => 'stores.dashboard'],
        ['route' => 'stores.index', 'label' => 'Boutiques', 'match' => 'stores.index'],
        ['route' => 'stores.create', 'label' => 'Nouvelle boutique', 'match' => 'stores.create', 'can' => 'stores.create'],
    ];
@endphp
<nav class="mb-6 flex gap-2 overflow-x-auto pb-1">
    @foreach($items as $item)
        @if(empty($item['can']) || auth()->user()->can($item['can']))
            <a href="{{ route($item['route']) }}"
               class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs($item['match']) ? 'bg-gp-primary text-white shadow-sm' : 'bg-white text-gp-muted ring-1 ring-gp-border hover:text-gp-text dark:bg-white/5 dark:ring-white/10 dark:hover:text-white' }}">
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
