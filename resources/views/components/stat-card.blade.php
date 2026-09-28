@props([
    'label',
    'value',
    'delta' => null,            // signed number, e.g. -1.4
    'deltaSuffix' => '%',
    'deltaCaption' => 'vs last month',
    'goodDirection' => 'up',    // 'up' = rising is good, 'down' = rising is bad
    'icon' => null,
    'hint' => null,
    'accent' => null,           // optional left accent bar (PAR ramp cards)
])

@php
    use App\Support\Format;

    $tone = $delta === null ? null : Format::deltaTone((float) $delta, $goodDirection);

    $deltaClasses = match ($tone) {
        'good' => 'text-success bg-[#E7F6EE]',
        'bad' => 'text-danger bg-[#FEECEA]',
        default => 'text-neutral-600 bg-neutral-100',
    };

    $rising = $delta !== null && (float) $delta > 0;
@endphp

<article {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-[12px] border border-neutral-200 bg-white p-5 shadow-card']) }}>
    @if($accent)
        <span class="absolute inset-y-0 left-0 w-1" style="background-color: {{ $accent }}" aria-hidden="true"></span>
    @endif

    <div class="flex items-start justify-between gap-3">
        <h3 class="text-[13px] font-medium text-neutral-500">{{ $label }}</h3>
        @if($icon)
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[8px] bg-primary-50 text-primary-700">
                <x-icon :name="$icon" />
            </span>
        @endif
    </div>

    <p class="mt-2 text-[26px] font-semibold leading-tight tracking-tight text-neutral-900 tabular-nums">{{ $value }}</p>

    @if($delta !== null || $hint)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            @if($delta !== null)
                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium tabular-nums {{ $deltaClasses }}">
                    <x-icon :name="$rising ? 'arrow-up' : 'arrow-down'" class="h-3.5 w-3.5" stroke="2.25" />
                    {{ ($rising ? '+' : '').number_format((float) $delta, 1) }}{{ $deltaSuffix }}
                </span>
                <span class="text-xs text-neutral-500">{{ $deltaCaption }}</span>
            @endif
            @if($hint)
                <span class="text-xs text-neutral-500">{{ $hint }}</span>
            @endif
        </div>
    @endif
</article>
