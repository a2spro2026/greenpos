{{-- Empty state premium --}}
@props([
    'title' => 'Aucune donnée',
    'text' => 'Aucun élément à afficher pour le moment.',
    'action' => null,
    'actionLabel' => null,
])
<div {{ $attributes->merge(['class' => 'gp-empty']) }}>
    <div class="gp-empty-icon" aria-hidden="true">
        {{ $icon ?? '' }}
        @unless(isset($icon))
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0H4"/></svg>
        @endunless
    </div>
    <p class="gp-empty-title">{{ $title }}</p>
    <p class="gp-empty-text">{{ $text }}</p>
    @if($action && $actionLabel)
        <a href="{{ $action }}" class="gp-btn-primary mt-5">{{ $actionLabel }}</a>
    @endif
    {{ $slot }}
</div>
