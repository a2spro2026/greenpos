@php
    $items = [
        ['route' => 'reports.dashboard', 'label' => 'Tableau de bord BI', 'match' => 'reports.dashboard'],
        ['route' => 'reports.sales', 'label' => 'Ventes', 'match' => 'reports.sales'],
        ['route' => 'reports.products', 'label' => 'Produits', 'match' => 'reports.products'],
        ['route' => 'reports.customers', 'label' => 'Clients', 'match' => 'reports.customers'],
        ['route' => 'reports.payments', 'label' => 'Paiements', 'match' => 'reports.payments', 'gate' => 'reports.financial'],
        ['route' => 'reports.stock', 'label' => 'Stock', 'match' => 'reports.stock'],
        ['route' => 'reports.pilotage', 'label' => 'Pilotage', 'match' => 'reports.pilotage'],
        ['route' => 'reports.extra', 'params' => ['type' => 'achats'], 'label' => 'Achats', 'match' => 'reports.extra', 'type' => 'achats'],
        ['route' => 'reports.extra', 'params' => ['type' => 'commandes'], 'label' => 'Commandes', 'match' => 'reports.extra', 'type' => 'commandes'],
        ['route' => 'reports.extra', 'params' => ['type' => 'production'], 'label' => 'Production', 'match' => 'reports.extra', 'type' => 'production'],
        ['route' => 'reports.extra', 'params' => ['type' => 'financier'], 'label' => 'Financier', 'match' => 'reports.extra', 'type' => 'financier', 'gate' => 'reports.financial'],
        ['route' => 'reports.extra', 'params' => ['type' => 'collaborateurs'], 'label' => 'Collaborateurs', 'match' => 'reports.extra', 'type' => 'collaborateurs'],
        ['route' => 'reports.documents', 'label' => 'Documents', 'match' => 'reports.documents'],
    ];
@endphp
<nav class="mb-6 flex gap-2 overflow-x-auto pb-1">
    @foreach($items as $item)
        @if(!isset($item['gate']) || Gate::check($item['gate']))
            <a href="{{ route($item['route'], $item['params'] ?? []) }}"
               class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition {{ (request()->routeIs($item['match']) && (! isset($item['type']) || request()->route('type') === $item['type'])) ? 'bg-gp-primary text-white shadow-sm' : 'bg-white text-gp-muted ring-1 ring-gp-border hover:text-gp-text dark:bg-white/5 dark:ring-white/10 dark:hover:text-white' }}">
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
