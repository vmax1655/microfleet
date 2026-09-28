@props([
    'name' => '',
    'size' => 'md',
    'tone' => 'primary',
])

@php
    $sizes = [
        'xs' => 'h-6 w-6 text-[10px]',
        'sm' => 'h-8 w-8 text-[11px]',
        'md' => 'h-9 w-9 text-xs',
        'lg' => 'h-12 w-12 text-sm',
        'xl' => 'h-16 w-16 text-lg',
    ];

    $tones = [
        'primary' => 'bg-primary-100 text-primary-800',
        'accent' => 'bg-accent-100 text-accent-700',
        'neutral' => 'bg-neutral-200 text-neutral-700',
        'inverse' => 'bg-primary-700 text-white',
    ];
@endphp

<span {{ $attributes->merge([
        'class' => 'inline-grid shrink-0 place-items-center rounded-full font-semibold uppercase '
            .($sizes[$size] ?? $sizes['md']).' '.($tones[$tone] ?? $tones['primary']),
    ]) }}
      title="{{ $name }}"
      aria-hidden="true">{{ \App\Support\Format::initials($name) }}</span>
