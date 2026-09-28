@props([
    'from' => 1,
    'to' => 20,
    'total' => 0,
    'current' => 1,
    'perPage' => 20,
])

@php
    $pages = (int) max(1, ceil($total / max(1, $perPage)));
    $window = collect(range(1, $pages))
        ->filter(fn ($p) => $p === 1 || $p === $pages || abs($p - $current) <= 1)
        ->values();
@endphp

<nav {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3 border-t border-neutral-200 px-4 py-3']) }}
     aria-label="Pagination">

    <p class="text-[13px] text-neutral-600 tabular-nums">
        Showing <span class="font-medium text-neutral-800">{{ number_format($from) }}–{{ number_format($to) }}</span>
        of <span class="font-medium text-neutral-800">{{ number_format($total) }}</span>
    </p>

    <div class="flex items-center gap-1">
        <button type="button"
                class="grid h-8 w-8 place-items-center rounded-[8px] border border-neutral-300 bg-white text-neutral-600 hover:bg-neutral-50 disabled:opacity-40"
                @disabled($current <= 1)
                aria-label="Previous page">
            <x-icon name="chevron-left" />
        </button>

        @php $prev = 0; @endphp
        @foreach($window as $p)
            @if($prev && $p - $prev > 1)
                <span class="px-1 text-neutral-400" aria-hidden="true">…</span>
            @endif
            <button type="button"
                    @class([
                        'h-8 min-w-8 rounded-[8px] border px-2 text-[13px] font-medium tabular-nums',
                        'border-primary-600 bg-primary-600 text-white' => $p === $current,
                        'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50' => $p !== $current,
                    ])
                    @if($p === $current) aria-current="page" @endif>{{ $p }}</button>
            @php $prev = $p; @endphp
        @endforeach

        <button type="button"
                class="grid h-8 w-8 place-items-center rounded-[8px] border border-neutral-300 bg-white text-neutral-600 hover:bg-neutral-50 disabled:opacity-40"
                @disabled($current >= $pages)
                aria-label="Next page">
            <x-icon name="chevron-right" />
        </button>
    </div>
</nav>
