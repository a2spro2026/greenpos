<nav class="mb-6 flex flex-wrap gap-2">
    @foreach([
        ['route' => 'crm.dashboard', 'label' => 'Dashboard'],
        ['route' => 'crm.pipeline', 'label' => 'Pipeline'],
        ['route' => 'crm.leads.index', 'label' => 'Leads'],
        ['route' => 'crm.opportunities.index', 'label' => 'Opportunités'],
        ['route' => 'crm.activities.index', 'label' => 'Activités'],
        ['route' => 'crm.calendar', 'label' => 'Calendrier'],
        ['route' => 'crm.emails.index', 'label' => 'Emails'],
        ['route' => 'crm.reports', 'label' => 'Rapports'],
    ] as $item)
        <a href="{{ route($item['route']) }}"
           class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index','.*', $item['route'])) ? 'bg-teal-600 text-white' : 'bg-gp-surface-2 text-gp-muted hover:text-gp-text' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
