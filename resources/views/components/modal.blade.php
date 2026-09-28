@props([
    'name',
    'title' => null,
    'subtitle' => null,
    'size' => 'md',      // sm | md | lg | xl
    'icon' => null,
    'tone' => 'neutral', // neutral | danger | warning | success — tints the header icon
])

@php
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
    ];

    $tones = [
        'neutral' => 'bg-primary-50 text-primary-700',
        'danger' => 'bg-[#FEECEA] text-danger',
        'warning' => 'bg-[#FEF0D6] text-warning',
        'success' => 'bg-[#E7F6EE] text-success',
        'info' => 'bg-[#EAF2FE] text-info',
    ];
@endphp

{{--
    Open with:  @click="$dispatch('open-modal', '{{ $name }}')"
    Close with: @click="$dispatch('close-modal')" or Escape.
--}}
<div x-data="{ open: false }"
     x-on:open-modal.window="
        const name = '{{ $name }}';
        const d = $event.detail;
        const match = d === name
            || (Array.isArray(d) && d[0] === name)
            || (typeof d === 'object' && d !== null && Object.values(d)[0] === name);
        if (match) open = true;
     "
     x-on:close-modal.window="open = false"
     x-show="open"
     x-cloak
     @keydown.escape.window="open = false"
     class="fixed inset-0 overflow-y-auto"
     :class="open ? '' : 'pointer-events-none'"
     style="z-index: 99999 !important;"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modal-title-{{ $name }}">

    <div x-show="open"
         x-transition.opacity.duration.150ms
         @click="open = false"
         class="fixed inset-0 bg-neutral-950/50"
         style="z-index: 99998 !important;"
         aria-hidden="true"></div>

    <div class="flex min-h-full items-start justify-center p-3 sm:px-4 pt-16 sm:pt-20 pb-8 text-center" style="position: relative; z-index: 99999 !important;">
        <div x-show="open"
             x-trap.noscroll="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-3 sm:scale-95"
             class="relative flex w-full {{ $sizes[$size] ?? $sizes['md'] }} flex-col overflow-hidden rounded-[12px] border border-neutral-200 bg-white shadow-flyout text-left"
             style="max-height: calc(100dvh - 6rem); max-height: calc(100vh - 6rem);">

            <header class="flex shrink-0 items-start gap-3 border-b border-neutral-200 px-5 py-3.5">
                @if($icon)
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $tones[$tone] ?? $tones['neutral'] }}">
                        <x-icon :name="$icon" />
                    </span>
                @endif

                <div class="min-w-0 flex-1">
                    <h2 id="modal-title-{{ $name }}" class="text-[15px] font-semibold text-neutral-800">{{ $title }}</h2>
                    @if($subtitle)
                        <p class="mt-0.5 text-[13px] text-neutral-500">{{ $subtitle }}</p>
                    @endif
                </div>

                <button type="button"
                        @click="open = false"
                        class="-mr-1 -mt-1 shrink-0 rounded-[8px] p-1.5 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-800"
                        aria-label="Close dialog">
                    <x-icon name="x" />
                </button>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4" style="overscroll-behavior: contain;">
                {{ $slot }}
            </div>

            @isset($footer)
                <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-neutral-200 bg-neutral-50 px-5 py-3.5">
                    {{ $footer }}
                </footer>
            @endisset
        </div>
    </div>
</div>
