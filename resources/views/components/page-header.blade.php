@props([
    'title',
    'subtitle' => null,
    'back' => null,
    'backLabel' => 'Back',
])

<div {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        @if($back)
            <a href="{{ $back }}" wire:navigate class="mb-1.5 inline-flex items-center gap-1.5 text-[13px] font-medium text-primary-700 hover:text-primary-800 hover:underline">
                <x-icon name="arrow-left" class="h-4 w-4" /> {{ $backLabel }}
            </a>
        @endif

        <h1 class="truncate text-2xl font-semibold tracking-tight text-neutral-800">{{ $title }}</h1>

        @if($subtitle)
            <p class="mt-1 text-sm text-neutral-500">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
