@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'p-5',
    'flush' => false,
])

{{-- White card: border-neutral-200, 12px radius, two-layer card shadow. --}}
<section {{ $attributes->merge(['class' => 'rounded-[12px] border border-neutral-200 bg-white shadow-card']) }}>
    @if($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-neutral-200 px-5 py-4">
            <div class="min-w-0">
                @if($title)
                    <h2 class="text-[15px] font-semibold text-neutral-800">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="mt-0.5 text-[13px] text-neutral-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $flush ? '' : $padding }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-neutral-200 px-5 py-3">{{ $footer }}</footer>
    @endisset
</section>
