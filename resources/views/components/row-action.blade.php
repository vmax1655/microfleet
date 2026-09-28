@props([
    'icon' => null,
    'href' => null,
    'danger' => false,
])

@php
    $classes = 'flex w-full items-center gap-2.5 rounded-[8px] px-2.5 py-2 text-sm '
        .($danger ? 'text-danger hover:bg-[#FEECEA]' : 'text-neutral-700 hover:bg-neutral-50');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="{{ $danger ? '' : 'text-neutral-500' }}" />@endif
        {{ $slot }}
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="{{ $danger ? '' : 'text-neutral-500' }}" />@endif
        {{ $slot }}
    </button>
@endif
