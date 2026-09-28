@props([
    'label',
    'route',
    'active' => false,
    'moduleKey' => null,
])

{{--
    Sub-module row: 44px indent, 13px text, dot marker.
    Active state carries a 3px accent bar flush against the sidebar's left edge.
--}}
<li>
    <a href="{{ route($route) }}"
       wire:navigate
       data-module-link
       @class([
           'group relative flex items-center overflow-hidden py-2 pl-[44px] pr-3 text-[13px] select-none rounded-r-[8px] transition-all duration-150 active:scale-[0.98]',
           'bg-primary-700 font-medium text-white shadow-sm' => $active,
           'text-primary-200 hover:bg-primary-700/25 hover:text-white hover:translate-x-0.5' => ! $active,
       ])
       @if($active) aria-current="page" @endif>

        @if($active)
            <span class="absolute inset-y-1 left-0 w-[3px] rounded-r-full bg-accent-500 transition-all duration-300" aria-hidden="true"></span>
        @endif

        <span @class([
            'absolute left-[26px] h-1.5 w-1.5 rounded-full transition-all duration-200',
            'bg-accent-500 scale-125 ring-2 ring-accent-400/40' => $active,
            'bg-primary-500 group-hover:bg-primary-300 group-hover:scale-110' => ! $active,
        ]) aria-hidden="true"></span>

        <span class="truncate transition-transform duration-150 group-active:translate-x-0.5">{{ $label }}</span>
    </a>
</li>
