@props([
    'label',
    'value',
    'tone' => 'default',   // default | danger | success | warning | muted
    'hint' => null,
    'mono' => true,
])

@php
    $valueTone = [
        'default' => 'text-neutral-900',
        'danger' => 'text-danger',
        'success' => 'text-success',
        'warning' => 'text-accent-700',
        'muted' => 'text-neutral-500',
    ][$tone] ?? 'text-neutral-900';
@endphp

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">{{ $label }}</dt>
    <dd class="mt-1 text-[15px] font-semibold {{ $mono ? 'tabular-nums' : '' }} {{ $valueTone }}">{{ $value }}</dd>
    @if($hint)
        <p class="mt-0.5 text-xs text-neutral-500">{{ $hint }}</p>
    @endif
</div>
