{{-- Skeleton loading block --}}
@props(['lines' => 4])
<div {{ $attributes->merge(['class' => 'space-y-3']) }} aria-busy="true" aria-label="Chargement">
    @for($i = 0; $i < $lines; $i++)
        <div class="gp-skeleton h-4" style="width: {{ 100 - ($i * 12) }}%"></div>
    @endfor
</div>
