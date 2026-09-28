@props(['label' => 'Row actions'])

{{-- The ⋯ menu at the end of every table row. --}}
<div x-data="{ open: false }" class="relative flex justify-end" @keydown.escape.stop="open = false">
    <button type="button"
            @click="open = !open"
            :aria-expanded="open ? 'true' : 'false'"
            class="rounded-[8px] p-1.5 text-neutral-500 transition-colors hover:bg-neutral-200 hover:text-neutral-800"
            aria-label="{{ $label }}">
        <x-icon name="more-horizontal" />
    </button>

    <div x-show="open"
         x-cloak
         x-transition.opacity.duration.150ms
         @click.outside="open = false"
         class="absolute right-0 top-full z-30 mt-1 w-48 rounded-[12px] border border-neutral-200 bg-white p-1.5 text-left shadow-flyout">
        {{ $slot }}
    </div>
</div>
