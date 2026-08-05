@php
    $tabs = [
        'dashboard' => ['label' => 'Santé', 'route' => 'system.dashboard'],
        'backups' => ['label' => 'Sauvegardes', 'route' => 'system.backups'],
        'alerts' => ['label' => 'Alertes', 'route' => 'system.alerts'],
        'journal' => ['label' => 'Journal', 'route' => 'system.journal'],
    ];
@endphp
<nav class="sys-tabs" aria-label="Navigation système">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}" class="sys-tab {{ ($active ?? '') === $key ? 'active' : '' }}">{{ $tab['label'] }}</a>
    @endforeach
</nav>
