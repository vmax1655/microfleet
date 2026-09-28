@props([
    'config' => [],
    'height' => 'h-72',
    'label' => 'Chart',
])

{{--
    Thin Chart.js wrapper. Every chart lives in its own component under
    components/charts/ and only hands a config array down to here, so the
    rendering library can be swapped in exactly one place.

    wire:ignore keeps Livewire from morphing the canvas out from under Chart.js.
--}}
<div x-data="ledgerChart({{ \App\Support\ChartConfig::js($config) }})"
     x-init="mount()"
     wire:ignore
     {{ $attributes->merge(['class' => 'relative w-full '.$height]) }}>
    <canvas x-ref="canvas" role="img" aria-label="{{ $label }}"></canvas>
</div>
