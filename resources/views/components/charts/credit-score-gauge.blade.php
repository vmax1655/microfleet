@props([
    'score' => 640,
    'min' => 300,
    'max' => 900,
    'height' => 'h-40',
])

@php
    $score = max($min, min($max, (int) $score));
    $pct = ($score - $min) / ($max - $min) * 100;

    // Band colours follow the PAR ramp logic: weak = danger, strong = primary.
    $color = match (true) {
        $score < 580 => '#B42318',
        $score < 670 => '#E8940F',
        $score < 740 => '#3FB39B',
        default => '#0F7A68',
    };

    $config = [
        'type' => 'doughnut',
        'data' => [
            'labels' => ['Score', 'Remaining'],
            'datasets' => [[
                'data' => [round($pct, 2), round(100 - $pct, 2)],
                'backgroundColor' => [$color, '#F0F4F2'],
                'borderWidth' => 0,
                'circumference' => 180,
                'rotation' => 270,
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '76%',
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['enabled' => false],
            ],
        ],
    ];

    $band = match (true) {
        $score < 580 => 'Poor',
        $score < 670 => 'Fair',
        $score < 740 => 'Good',
        default => 'Excellent',
    };
@endphp

<div class="relative">
    <x-chart :config="$config" :height="$height" label="Credit score gauge showing {{ $score }} out of {{ $max }}" />

    <div class="pointer-events-none absolute inset-x-0 bottom-1 text-center">
        <p class="text-3xl font-semibold tabular-nums text-neutral-900">{{ $score }}</p>
        <p class="text-[13px] font-medium" style="color: {{ $color }}">{{ $band }}</p>
        <p class="mt-0.5 text-xs text-neutral-500 tabular-nums">Range {{ $min }}–{{ $max }}</p>
    </div>
</div>
