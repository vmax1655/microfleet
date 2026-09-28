@props([
    'variant' => 'secondary',
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'href' => null,
    'type' => 'button',
])

@php
    // Design-system rule: exactly ONE primary button per screen.
    $variants = [
        'primary' => 'bg-primary-600 text-white border border-transparent hover:bg-primary-700 focus-visible:ring-primary-600',
        'secondary' => 'bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 focus-visible:ring-primary-600',
        'ghost' => 'bg-transparent text-neutral-600 border border-transparent hover:bg-neutral-100 hover:text-neutral-900',
        'danger' => 'bg-danger text-white border border-transparent hover:bg-[#962014]',
        'danger-outline' => 'bg-white text-danger border border-[#F5B5AE] hover:bg-[#FEECEA]',
        'success' => 'bg-success text-white border border-transparent hover:bg-[#0F6539]',
        'link' => 'bg-transparent text-primary-700 border border-transparent hover:text-primary-800 hover:underline px-0',
    ];

    $sizes = [
        'xs' => 'h-7 px-2 text-xs gap-1 rounded-[6px]',
        'sm' => 'h-8 px-2.5 text-[13px] gap-1.5 rounded-[8px]',
        'md' => 'h-9 px-3.5 text-sm gap-2 rounded-[8px]',
        'lg' => 'h-11 px-5 text-sm gap-2 rounded-[8px]',
    ];

    $classes = 'inline-flex shrink-0 items-center justify-center font-medium transition-colors '
        .'disabled:cursor-not-allowed disabled:opacity-50 '
        .($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['secondary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="shrink-0" />@endif
        {{ $slot }}
        @if($iconRight)<x-icon :name="$iconRight" class="shrink-0" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="shrink-0" />@endif
        {{ $slot }}
        @if($iconRight)<x-icon :name="$iconRight" class="shrink-0" />@endif
    </button>
@endif
