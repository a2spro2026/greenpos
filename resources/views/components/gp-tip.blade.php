{{-- Contextual tip / form help --}}
@props(['text' => ''])
<span {{ $attributes->merge(['class' => 'gp-has-tip inline-flex h-4 w-4 items-center justify-center rounded-full bg-gp-primary-soft text-[10px] font-bold text-gp-primary', 'data-gp-tip' => $text, 'tabindex' => '0', 'role' => 'img', 'aria-label' => $text]) }}>?</span>
