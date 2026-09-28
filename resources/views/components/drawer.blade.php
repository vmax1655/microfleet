@props([
    'name',
    'title' => null,
    'subtitle' => null,
    'width' => 'max-w-xl',
])

{{--
    Right-hand drawer for record review (KYC, credit assessment).
    Open with: @click="$dispatch('open-drawer', '{{ $name }}')"
--}}
<div x-data="{ open: false }"
     x-on:open-drawer.window="if ($event.detail === '{{ $name }}') open = true"
     x-on:close-drawer.window="open = false"
     x-show="open"
     x-cloak
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-50"
     role="dialog"
     aria-modal="true"
     aria-labelledby="drawer-title-{{ $name }}">

    <div x-show="open"
         x-transition.opacity.duration.200ms
         @click="open = false"
         class="absolute inset-0 bg-neutral-950/50"
         aria-hidden="true"></div>

    <div x-show="open"
         x-trap.noscroll="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="absolute inset-y-0 right-0 flex w-full {{ $width }} flex-col bg-white shadow-flyout">

        <header class="flex items-start gap-3 border-b border-neutral-200 px-5 py-4">
            <div class="min-w-0 flex-1">
                <h2 id="drawer-title-{{ $name }}" class="text-[15px] font-semibold text-neutral-800">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-0.5 text-[13px] text-neutral-500">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button"
                    @click="open = false"
                    class="-mr-1 shrink-0 rounded-[8px] p-1.5 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-800"
                    aria-label="Close panel">
                <x-icon name="x" />
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="border-t border-neutral-200 bg-neutral-50 px-5 py-3.5">
                {{ $footer }}
            </footer>
        @endisset
    </div>
</div>
