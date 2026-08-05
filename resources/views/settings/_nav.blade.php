@php
    $nav = [
        ['key' => 'index', 'label' => 'Vue d\'ensemble', 'route' => 'settings.index', 'match' => 'settings.index'],
        ['key' => 'company', 'label' => 'Entreprise', 'route' => 'settings.company', 'match' => 'settings.company'],
        ['key' => 'stores', 'label' => 'Boutiques', 'route' => 'settings.stores', 'match' => 'settings.stores'],
        ['key' => 'tax', 'label' => 'Fiscalité', 'route' => 'settings.section', 'params' => ['section' => 'tax'], 'match' => null],
        ['key' => 'currencies', 'label' => 'Devises', 'route' => 'settings.section', 'params' => ['section' => 'currencies'], 'match' => null],
        ['key' => 'languages', 'label' => 'Langues', 'route' => 'settings.section', 'params' => ['section' => 'languages'], 'match' => null],
        ['key' => 'numbering', 'label' => 'Numérotation', 'route' => 'settings.section', 'params' => ['section' => 'numbering'], 'match' => null],
        ['key' => 'pos', 'label' => 'POS & Caisse', 'route' => 'settings.section', 'params' => ['section' => 'pos'], 'match' => null],
        ['key' => 'invoicing', 'label' => 'Facturation', 'route' => 'settings.section', 'params' => ['section' => 'invoicing'], 'match' => null],
        ['key' => 'payments', 'label' => 'Paiements', 'route' => 'settings.section', 'params' => ['section' => 'payments'], 'match' => null],
        ['key' => 'notifications', 'label' => 'Notifications', 'route' => 'settings.section', 'params' => ['section' => 'notifications'], 'match' => null],
        ['key' => 'security', 'label' => 'Sécurité', 'route' => 'settings.section', 'params' => ['section' => 'security'], 'match' => null],
        ['key' => 'backup', 'label' => 'Sauvegarde', 'route' => 'system.backups', 'match' => 'system.*'],
        ['key' => 'appearance', 'label' => 'Apparence', 'route' => 'settings.section', 'params' => ['section' => 'appearance'], 'match' => null],
        ['key' => 'branding', 'label' => 'Branding', 'route' => 'branding.index', 'match' => 'branding.*'],
        ['key' => 'integrations', 'label' => 'Intégrations', 'route' => 'settings.section', 'params' => ['section' => 'integrations'], 'match' => null],
    ];
    $current = $section ?? 'index';
@endphp

<aside class="settings-side shrink-0 lg:w-56">
    <div class="gp-card !p-2 sticky top-20">
        <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-gp-muted">Configuration</p>
        <nav class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible pb-1 lg:pb-0">
            @foreach($nav as $item)
                @php
                    $href = isset($item['params']) ? route($item['route'], $item['params']) : route($item['route']);
                    $active = ($item['key'] === $current) || (!empty($item['match']) && request()->routeIs($item['match']));
                    if ($item['key'] !== 'index' && $item['key'] !== 'company' && $item['key'] !== 'stores' && $item['key'] !== 'branding' && $item['key'] !== 'backup') {
                        $active = ($current === $item['key']);
                    }
                    if ($item['key'] === 'branding') {
                        $active = request()->routeIs('branding.*');
                    }
                    if ($item['key'] === 'backup') {
                        $active = request()->routeIs('system.*') || ($current === 'backup');
                    }
                @endphp
                <a href="{{ $href }}"
                   class="settings-nav-link whitespace-nowrap {{ $active ? 'settings-nav-link-active' : '' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</aside>
