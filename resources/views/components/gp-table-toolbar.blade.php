{{-- Table toolbar: density hint (global density via profile menu) --}}
@props(['title' => null])
<div {{ $attributes->merge(['class' => 'gp-table-toolbar']) }}>
    <div class="min-w-0">
        @if($title)
            <h2 class="text-sm font-bold text-gp-text dark:text-white">{{ $title }}</h2>
        @endif
        {{ $slot }}
    </div>
    <div class="flex items-center gap-1 rounded-xl border border-gp-border bg-gp-surface p-1">
        <button type="button" class="gp-density-btn rounded-lg px-2.5 py-1 text-[11px] font-semibold text-gp-muted transition hover:text-gp-text" data-density="compact" title="Densité compacte">Compact</button>
        <button type="button" class="gp-density-btn rounded-lg px-2.5 py-1 text-[11px] font-semibold text-gp-muted transition hover:text-gp-text" data-density="comfortable" title="Densité confortable">Normal</button>
        <button type="button" class="gp-density-btn rounded-lg px-2.5 py-1 text-[11px] font-semibold text-gp-muted transition hover:text-gp-text" data-density="spacious" title="Densité aérée">Aéré</button>
    </div>
</div>
