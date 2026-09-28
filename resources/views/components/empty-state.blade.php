@props([
    'icon' => 'inbox',
    'heading' => 'Nothing here yet',
    'help' => null,
    'actionLabel' => null,
    'actionHref' => null,
    'actionIcon' => 'plus',
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 text-center '.($compact ? 'py-10' : 'py-16')]) }}>
    <span class="grid h-12 w-12 place-items-center rounded-full bg-primary-50 text-primary-600">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>

    <h3 class="mt-4 text-[15px] font-semibold text-neutral-800">{{ $heading }}</h3>

    @if($help)
        <p class="mt-1 max-w-sm text-[13px] text-neutral-500">{{ $help }}</p>
    @endif

    @if($actionLabel)
        <div class="mt-5">
            <x-btn variant="primary" :icon="$actionIcon" :href="$actionHref">{{ $actionLabel }}</x-btn>
        </div>
    @endif

    {{ $slot }}
</div>
