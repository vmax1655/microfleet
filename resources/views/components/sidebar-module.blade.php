@props([
    'moduleKey',
    'label',
    'icon',
    'count' => 5,
])

{{--
    Module row: icon + label + count chip + chevron.
    <button aria-expanded> controlling the sub-list, per the accessibility contract.
    Enter/Space fire click natively — no extra key handling needed.
--}}
<button type="button"
        data-module-btn
        @click="toggleModule('{{ $moduleKey }}')"
        :aria-expanded="(rail ? flyout === '{{ $moduleKey }}' : isOpen('{{ $moduleKey }}')) ? 'true' : 'false'"
        aria-controls="submenu-{{ $moduleKey }}"
        :title="rail ? '{{ $label }}' : null"
        class="group relative mx-2 flex w-[calc(100%-1rem)] items-center gap-3 overflow-hidden rounded-[8px] px-3 py-2.5 text-sm select-none transition-all duration-200 active:scale-[0.98]"
        :class="(rail ? flyout === '{{ $moduleKey }}' : isOpen('{{ $moduleKey }}'))
            ? 'bg-primary-700 text-white shadow-sm'
            : 'text-primary-100 hover:bg-primary-700/40 hover:text-white'">

    <span class="grid h-5 w-5 shrink-0 place-items-center transition-transform duration-200 group-hover:scale-110 group-active:scale-95">
        <x-icon :name="$icon" class="shrink-0" />
    </span>

    <span x-show="!rail" x-cloak class="flex-1 truncate text-left font-medium">{{ $label }}</span>

    <span x-show="!rail"
          x-cloak
          class="shrink-0 rounded-full px-1.5 py-0.5 text-[11px] font-semibold leading-none transition-all duration-200"
          :class="isOpen('{{ $moduleKey }}')
              ? 'bg-accent-500 text-primary-950 scale-105 shadow-sm'
              : 'bg-primary-700/70 text-primary-200 group-hover:text-white'">
        {{ $count }}
    </span>

    <x-icon name="chevron-right"
            x-show="!rail"
            x-cloak
            class="shrink-0 transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
            ::class="isOpen('{{ $moduleKey }}') ? 'rotate-90 text-accent-400' : 'text-primary-400 group-hover:text-primary-200'" />
</button>
