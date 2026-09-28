@props([
    'name',
    'stroke' => '1.75',
])

{{-- Lucide icon, 18px / stroke 1.75 by default (design system). --}}
<svg {{ $attributes->merge(['class' => 'h-[18px] w-[18px]']) }}
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="{{ $stroke }}"
     stroke-linecap="round"
     stroke-linejoin="round"
     aria-hidden="true"
     focusable="false">{!! \App\Support\Icons::path($name) !!}</svg>
