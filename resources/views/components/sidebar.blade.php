@php
    use App\Support\Nav;
    use App\Support\Rbac;

    $routeName   = request()->route()?->getName();
    $activeRoute = Nav::activeRoute($routeName);
    $dashboard   = Nav::dashboard();

    // Only show modules the current user is allowed to view
    $userRole = auth()->user()?->role;
    $modules = collect(Nav::modules())
        ->map(function ($module) use ($userRole) {
            $module['items'] = Rbac::visibleItems($userRole, $module);

            return $module;
        })
        ->filter(fn ($module) => $module['items'] !== [])
        ->values()
        ->all();
@endphp

{{--
    Fixed 264px sidebar, collapsible to a 68px icon rail.
    Below lg it becomes an off-canvas drawer; the accordion behaviour is identical
    in both modes. All state lives in the `ledgerShell` Alpine component.
--}}
<aside class="fixed inset-y-0 left-0 z-40 flex w-[264px] flex-col bg-primary-800 transition-all duration-200"
       :class="(mobileOpen ? 'translate-x-0 ' : '-translate-x-full lg:translate-x-0 ') + (collapsed ? 'lg:w-[68px]' : 'lg:w-[264px]')"
       aria-label="Main navigation">

    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-primary-700/60 px-4">
        <a href="{{ route('dashboard') }}"
           wire:navigate
           class="flex items-center gap-2.5 rounded-[8px] focus-visible:ring-offset-primary-800">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[8px] bg-accent-500 text-primary-950">
                <x-icon name="truck" class="h-[18px] w-[18px]" />
            </span>
            <span x-show="!rail" x-cloak class="text-[17px] font-semibold tracking-tight text-white">Microfleet</span>
        </a>

        <button type="button"
                @click="mobileOpen = false"
                class="ml-auto rounded-[8px] p-1.5 text-primary-200 hover:bg-primary-700/50 hover:text-white lg:hidden"
                aria-label="Close navigation">
            <x-icon name="x" />
        </button>
    </div>

    {{-- Navigation tree --}}
    <nav class="scroll-slim flex-1 overflow-y-auto overflow-x-visible py-3" aria-label="Modules">
        <ul class="space-y-0.5">

            {{-- Standalone item --}}
            <li>
                @php $dashActive = $activeRoute === 'dashboard'; @endphp
                <a href="{{ route($dashboard['route']) }}"
                   wire:navigate
                   data-module-link
                   @class([
                       'relative mx-2 flex items-center gap-3 overflow-hidden rounded-[8px] px-3 py-2.5 text-sm select-none transition-all duration-150 active:scale-[0.98] group',
                       'bg-primary-700 font-medium text-white shadow-sm' => $dashActive,
                       'text-primary-100 hover:bg-primary-700/40 hover:text-white' => ! $dashActive,
                   ])
                   @if($dashActive) aria-current="page" @endif
                   :title="rail ? '{{ $dashboard['label'] }}' : null">
                    <span class="grid h-5 w-5 shrink-0 place-items-center transition-transform duration-200 group-hover:scale-110 group-active:scale-95">
                        <x-icon :name="$dashboard['icon']" class="shrink-0" />
                    </span>
                    <span x-show="!rail" x-cloak class="truncate">{{ $dashboard['label'] }}</span>
                </a>
            </li>

            <li class="px-4 pb-1 pt-4" x-show="!rail" x-cloak>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-primary-400">Modules</p>
            </li>
            <li class="mx-4 my-3 h-px bg-primary-700/50" x-show="rail" x-cloak aria-hidden="true"></li>

            {{-- 5 collapsible modules --}}
            @foreach($modules as $module)
                @php
                    $key = $module['key'];
                    $moduleActive = collect($module['items'])->contains(fn ($i) => $i['route'] === $activeRoute);
                @endphp

                <li class="relative">
                    <x-sidebar-module
                        :module-key="$key"
                        :label="$module['label']"
                        :icon="$module['icon']"
                        :count="count($module['items'])" />

                    {{-- Inline accordion panel (expanded sidebar) --}}
                    <div id="submenu-{{ $key }}"
                         x-show="!rail"
                         x-cloak
                         class="submenu-grid"
                         :class="isOpen('{{ $key }}') ? 'is-open' : ''"
                         :aria-hidden="isOpen('{{ $key }}') ? 'false' : 'true'">
                        <div class="submenu-inner">
                            <ul class="pb-1 pt-1">
                                @foreach($module['items'] as $item)
                                    <x-sidebar-sub-item
                                        :label="$item['label']"
                                        :route="$item['route']"
                                        :active="$activeRoute === $item['route']"
                                        :module-key="$key" />
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    {{-- Floating flyout (icon rail) --}}
                    <div x-show="flyout === '{{ $key }}'"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-x-1"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @click.outside="flyout === '{{ $key }}' && (flyout = null)"
                         class="absolute left-full top-0 z-50 ml-2 w-64 rounded-[12px] border border-primary-700 bg-primary-800 p-2 shadow-flyout"
                         role="group"
                         aria-label="{{ $module['label'] }}">
                        <p class="border-b border-primary-700/60 px-3 pb-2 pt-1.5 text-[11px] font-semibold uppercase tracking-wider text-accent-300">
                            {{ $module['label'] }}
                        </p>
                        <ul class="pt-1.5">
                            @foreach($module['items'] as $item)
                                @php $subActive = $activeRoute === $item['route']; @endphp
                                <li>
                                    <a href="{{ route($item['route']) }}"
                                       wire:navigate
                                       data-module-link
                                       @class([
                                           'relative flex items-center gap-2.5 overflow-hidden rounded-[8px] px-3 py-2 text-[13px] select-none transition-all duration-150 active:scale-[0.98]',
                                           'bg-primary-700 font-medium text-white shadow-sm' => $subActive,
                                           'text-primary-200 hover:bg-primary-700/50 hover:text-white' => ! $subActive,
                                       ])
                                       @if($subActive) aria-current="page" @endif>
                                        <span @class([
                                            'h-1.5 w-1.5 shrink-0 rounded-full transition-transform duration-200',
                                            'bg-accent-500 scale-125 ring-2 ring-accent-400/40' => $subActive,
                                            'bg-primary-500' => ! $subActive,
                                        ])></span>
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Rail toggle --}}
    <div class="shrink-0 border-t border-primary-700/60 p-2">
        <button type="button"
                @click="toggleCollapse()"
                class="hidden w-full items-center gap-3 rounded-[8px] px-3 py-2.5 text-sm text-primary-200 transition-colors hover:bg-primary-700/40 hover:text-white lg:flex"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <span class="shrink-0">
                <x-icon name="chevrons-left" x-show="!collapsed" x-cloak />
                <x-icon name="chevrons-right" x-show="collapsed" x-cloak />
            </span>
            <span x-show="!rail" x-cloak>Collapse</span>
        </button>
    </div>
</aside>
