@props([
    'value' => 0,          // 0-100
    'label' => null,
    'caption' => null,
    'tone' => 'auto',      // auto | primary | success | warning | danger
    'size' => 'md',
    'showValue' => true,
    'thresholds' => [70, 90], // below first = danger, below second = warning, else success
])

@php
    $pct = max(0, min(100, (float) $value));

    $resolved = $tone;
    if ($tone === 'auto') {
        $resolved = match (true) {
            $pct < $thresholds[0] => 'danger',
            $pct < $thresholds[1] => 'warning',
            default => 'success',
        };
    }

    $bar = [
        'primary' => 'bg-primary-600',
        'success' => 'bg-success',
        'warning' => 'bg-accent-500',
        'danger' => 'bg-danger',
    ][$resolved] ?? 'bg-primary-600';

    $text = [
        'primary' => 'text-primary-700',
        'success' => 'text-success',
        'warning' => 'text-accent-700',
        'danger' => 'text-danger',
    ][$resolved] ?? 'text-primary-700';

    $height = ['sm' => 'h-1.5', 'md' => 'h-2', 'lg' => 'h-2.5'][$size] ?? 'h-2';
@endphp

<div {{ $attributes->merge(['class' => 'min-w-[120px]']) }}>
    @if($label || $showValue)
        <div class="mb-1 flex items-baseline justify-between gap-2">
            @if($label)<span class="text-[13px] text-neutral-600">{{ $label }}</span>@endif
            @if($showValue)
                <span class="text-[13px] font-semibold tabular-nums {{ $text }}">{{ number_format($pct, 1) }}%</span>
            @endif
        </div>
    @endif

    <div class="w-full overflow-hidden rounded-full bg-neutral-200 {{ $height }}"
         role="meter"
         aria-valuenow="{{ round($pct, 1) }}"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-label="{{ $label ?? 'Progress' }}">
        <div class="{{ $height }} rounded-full transition-[width] duration-500 {{ $bar }}" style="width: {{ $pct }}%"></div>
    </div>

    @if($caption)
        <p class="mt-1 text-xs text-neutral-500">{{ $caption }}</p>
    @endif
</div>
