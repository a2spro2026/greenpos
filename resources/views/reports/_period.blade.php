@php
    $pf = $filters['from'] ?? now()->startOfMonth();
    $pt = $filters['to'] ?? now();
    $keep = request()->except(['from', 'to', 'page']);
    $presets = [
        "Aujourd'hui" => [now()->toDateString(), now()->toDateString()],
        '7 derniers jours' => [now()->subDays(6)->toDateString(), now()->toDateString()],
        'Ce mois' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        'Mois précédent' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'Cette année' => [now()->startOfYear()->toDateString(), now()->toDateString()],
    ];
@endphp
<div class="mb-3 flex flex-wrap items-center gap-2 text-sm">
    <span class="font-semibold">Période : du {{ $pf->format('d/m/Y') }} au {{ $pt->format('d/m/Y') }}</span>
    @unless(request()->filled('from'))<span class="text-xs text-gp-muted">(par défaut : mois en cours — le tableau de bord POS affiche la journée)</span>@endunless
    @foreach($presets as $label => [$f, $t])
        <a href="{{ request()->url().'?'.http_build_query(array_merge($keep, ['from' => $f, 'to' => $t])) }}"
           class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $pf->toDateString() === $f && $pt->toDateString() === $t ? 'bg-gp-primary text-white ring-gp-primary' : 'ring-gp-border text-gp-muted hover:text-gp-text dark:ring-white/10' }}">{{ $label }}</a>
    @endforeach
</div>
