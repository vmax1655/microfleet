@props(['title' => null])

@php
    use App\Support\Rbac;

    $authUser = auth()->user();
    $role = $authUser?->role;

    $currentBranch = $authUser?->branch ?: 'Malolos Main Depot';

    $depotModels = \App\Models\Depot::withCount('vehicles')->orderBy('name')->get();
    $depots = $depotModels->map(function ($d) {
        return [
            'id' => $d->id,
            'name' => $d->name,
            'city' => $d->address ?: 'Bulacan',
            'vehicles' => $d->vehicles_count,
        ];
    })->values()->all();

    if (empty($depots)) {
        $depots = [
            ['id' => 1, 'name' => 'Malolos Main Depot', 'city' => 'Malolos, Bulacan', 'vehicles' => 0],
        ];
    }

    $initialDepotIndex = 0;
    foreach ($depots as $idx => $d) {
        if ($d['name'] === $currentBranch) {
            $initialDepotIndex = $idx;
            break;
        }
    }

    $notifications = collect([
        ['icon' => 'fuel', 'tone' => 'danger', 'title' => 'Fuel variance flagged', 'body' => 'MC-104 is 22% over predicted fuel use', 'time' => '12m ago', 'route_name' => 'intelligence.variance', 'route' => route('intelligence.variance')],
        ['icon' => 'navigation', 'tone' => 'info', 'title' => 'Dispatch ready for checkout', 'body' => 'DSP-2026-0182 awaiting starting odometer', 'time' => '28m ago', 'route_name' => 'fleet.dispatch', 'route' => route('fleet.dispatch')],
        ['icon' => 'wrench', 'tone' => 'warning', 'title' => 'Maintenance threshold reached', 'body' => 'PUV-118 is due for preventive service', 'time' => '1h ago', 'route_name' => 'logistics.maintenance', 'route' => route('logistics.maintenance')],
        ['icon' => 'shield-check', 'tone' => 'warning', 'title' => 'Driver license review', 'body' => 'R. Santos expires on 30 Sep 2026', 'time' => 'Today', 'route_name' => 'fleet.drivers', 'route' => route('fleet.drivers')],
    ])->filter(fn ($item) => Rbac::allowsRoute($role, $item['route_name'], 'view'))->values()->all();

    $user = [
        'name' => $authUser?->name ?? 'Guest',
        'role' => $authUser?->role ?? '-',
        'branch' => $authUser?->branch ?? '-',
    ];

    $searchItems = collect([
        ['label' => 'MC-104', 'meta' => 'Vehicle - In transit - Malolos Main Depot', 'route_name' => 'fleet.vehicles', 'url' => route('fleet.vehicles')],
        ['label' => 'MCB-204', 'meta' => 'Vehicle - Assigned - Malolos Main Depot', 'route_name' => 'fleet.vehicles', 'url' => route('fleet.vehicles')],
        ['label' => 'VAN-022', 'meta' => 'Vehicle - Available - Malolos Main Depot', 'route_name' => 'fleet.vehicles', 'url' => route('fleet.vehicles')],
        ['label' => 'PUV-118', 'meta' => 'Vehicle - Maintenance - Paombong Satellite Yard', 'route_name' => 'fleet.vehicles', 'url' => route('fleet.vehicles')],
        ['label' => 'DSP-2026-0182', 'meta' => 'Dispatch - Awaiting checkout', 'route_name' => 'fleet.dispatch', 'url' => route('fleet.dispatch')],
        ['label' => 'TRP-2026-0923-014', 'meta' => 'Trip - Center 04 / Brgy. San Isidro', 'route_name' => 'fleet.trips', 'url' => route('fleet.trips')],
        ['label' => 'R. Santos', 'meta' => 'Driver - License review due', 'route_name' => 'fleet.drivers', 'url' => route('fleet.drivers')],
        ['label' => 'OR-FUEL-4421', 'meta' => 'Fuel receipt - MC-104', 'route_name' => 'logistics.fuel', 'url' => route('logistics.fuel')],
        ['label' => 'Route RT-MAL-004', 'meta' => 'Route - Malolos to San Isidro', 'route_name' => 'logistics.routes', 'url' => route('logistics.routes')],
        ['label' => 'Cost variance review', 'meta' => 'ML variance and trip cost analysis', 'route_name' => 'intelligence.variance', 'url' => route('intelligence.variance')],
    ])->filter(fn ($item) => Rbac::allowsRoute($role, $item['route_name'], 'view'))->values()->all();
@endphp

<header x-data="topbarRuntime(@js($depots), @js($notifications), @js($searchItems), {{ $initialDepotIndex }})"
        class="sticky top-0 z-50 border-b border-neutral-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
    <div class="mx-auto flex h-16 w-full max-w-content items-center gap-3 px-4 sm:px-6">
        <button type="button"
                @click="mobileOpen = true"
                class="-ml-1 rounded-[8px] p-2 text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 lg:hidden"
                aria-label="Open navigation">
            <x-icon name="menu" />
        </button>

        <form class="relative hidden min-w-0 flex-1 sm:block" role="search" onsubmit="return false;" @keydown.escape="clearSearch()">
            <label for="global-search" class="sr-only">Search vehicles, trips, drivers, or receipts</label>
            <div class="relative max-w-md">
                <span class="pointer-events-none absolute inset-y-0 left-0 grid w-9 place-items-center text-neutral-400">
                    <x-icon name="search" />
                </span>
                <input id="global-search"
                       type="search"
                       x-model.debounce.150ms="searchQuery"
                       @focus="searchOpen = true"
                       @input="searchOpen = true"
                       placeholder="Search plate, trip, driver, or receipt..."
                       class="w-full rounded-[8px] border border-neutral-300 bg-neutral-50 py-2 pl-9 pr-3 text-sm text-neutral-800 placeholder:text-neutral-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-600/30">
            </div>
            <div x-show="searchOpen && searchQuery.length > 0"
                 x-cloak
                 x-transition.opacity.duration.150ms
                 @click.outside="searchOpen = false"
                 class="absolute left-0 z-40 mt-2 w-[420px] max-w-[calc(100vw-2rem)] rounded-[12px] border border-neutral-200 bg-white p-1.5 shadow-flyout">
                <div class="flex items-center justify-between px-2.5 py-1.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400">Live search</p>
                    <span class="text-[11px] text-neutral-400" x-text="`${filteredSearch.length} result${filteredSearch.length === 1 ? '' : 's'}`"></span>
                </div>
                <template x-if="filteredSearch.length > 0">
                    <ul class="max-h-72 overflow-y-auto">
                        <template x-for="item in filteredSearch" :key="item.label">
                            <li>
                                <a :href="item.url"
                                   class="flex items-start gap-2.5 rounded-[8px] px-2.5 py-2 hover:bg-primary-50">
                                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary-50 text-primary-700">
                                        <x-icon name="search" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-neutral-800" x-text="item.label"></span>
                                        <span class="block truncate text-xs text-neutral-500" x-text="item.meta"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </template>
                <p x-show="filteredSearch.length === 0" class="px-2.5 py-4 text-sm text-neutral-500">No matching fleet record found.</p>
            </div>
        </form>

        <span class="flex-1 sm:hidden"></span>

        <div class="relative hidden md:block" x-data="{ open: false }" @keydown.escape="open = false">
            <button type="button"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    class="flex items-center gap-2 rounded-[8px] border border-neutral-300 bg-white px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                <x-icon name="building-2" class="text-neutral-500" />
                <span class="max-w-[170px] truncate" x-text="selectedDepot.name">{{ $depots[$initialDepotIndex]['name'] ?? $depots[0]['name'] }}</span>
                <x-icon name="chevron-down" class="text-neutral-400" />
            </button>

            <div x-show="open"
                 x-cloak
                 x-transition.opacity.duration.150ms
                 @click.outside="open = false"
                 class="absolute right-0 z-40 mt-2 w-80 rounded-[12px] border border-neutral-200 bg-white p-1.5 shadow-flyout max-h-96 overflow-y-auto">
                <div class="flex items-center justify-between px-2.5 py-1.5 border-b border-neutral-100 mb-1">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400">Switch depot</p>
                    <span class="text-[11px] font-semibold text-primary-700 bg-primary-50 px-1.5 py-0.5 rounded" x-text="depots.length + ' depots'"></span>
                </div>
                <template x-for="(depot, i) in depots" :key="depot.id || depot.name">
                    <button type="button"
                            @click="switchDepot(i); open = false"
                            class="flex w-full items-start gap-2.5 rounded-[8px] px-2.5 py-2 text-left text-sm hover:bg-primary-50 transition-colors">
                        <span class="mt-0.5 shrink-0" :class="selectedDepotIndex === i ? 'text-primary-600' : 'text-neutral-300'">
                            <x-icon name="check" x-show="selectedDepotIndex === i" x-cloak />
                            <x-icon name="circle" x-show="selectedDepotIndex !== i" x-cloak />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium text-neutral-800" x-text="depot.name"></span>
                            <span class="block truncate text-xs text-neutral-500" x-text="depot.city + ' · ' + depot.vehicles + ' vehicle' + (depot.vehicles === 1 ? '' : 's')"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
            <button type="button"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    class="relative rounded-[8px] p-2 text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900"
                    :aria-label="`Notifications, ${unreadCount} unread`">
                <x-icon name="bell" />
                <span x-show="unreadCount > 0"
                      x-cloak
                      class="absolute -right-0.5 -top-0.5 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-danger px-1 text-[10px] font-semibold text-white"
                      x-text="unreadCount">
                    {{ count($notifications) }}
                </span>
            </button>

            <div x-show="open"
                 x-cloak
                 x-transition.opacity.duration.150ms
                 @click.outside="open = false"
                 class="absolute right-0 z-40 mt-2 w-[340px] max-w-[calc(100vw-2rem)] rounded-[12px] border border-neutral-200 bg-white shadow-flyout">
                <div class="flex items-center justify-between border-b border-neutral-200 px-4 py-3">
                    <h2 class="text-sm font-semibold text-neutral-800">Notifications</h2>
                    <button type="button"
                            @click="markAllRead()"
                            class="text-xs font-medium text-primary-700 hover:text-primary-800 disabled:cursor-not-allowed disabled:text-neutral-400"
                            :disabled="unreadCount === 0">
                        Mark all read
                    </button>
                </div>
                <ul class="max-h-80 overflow-y-auto divide-y divide-neutral-100">
                    @foreach($notifications as $n)
                        <li x-show="!notifications[{{ $loop->index }}].read" x-cloak>
                            <a href="{{ $n['route'] }}"
                               @click="markRead({{ $loop->index }})"
                               class="flex gap-3 px-4 py-3 hover:bg-neutral-50">
                                <span @class([
                                    'mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full',
                                    'bg-[#FEECEA] text-danger' => $n['tone'] === 'danger',
                                    'bg-[#FEF0D6] text-warning' => $n['tone'] === 'warning',
                                    'bg-[#E7F6EE] text-success' => $n['tone'] === 'success',
                                    'bg-[#EAF2FE] text-info' => $n['tone'] === 'info',
                                ])>
                                    <x-icon :name="$n['icon']" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-neutral-800">{{ $n['title'] }}</span>
                                    <span class="block truncate text-xs text-neutral-500">{{ $n['body'] }}</span>
                                    <span class="mt-0.5 block text-[11px] text-neutral-400">{{ $n['time'] }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p x-show="unreadCount === 0" x-cloak class="px-4 py-6 text-center text-sm text-neutral-500">All caught up. New fleet alerts will appear here.</p>
                @if(Rbac::allowsRoute($role, 'fleet.dispatch', 'view'))
                    <div class="border-t border-neutral-200 px-4 py-2.5">
                        <a href="{{ route('fleet.dispatch') }}" wire:navigate class="text-xs font-medium text-primary-700 hover:text-primary-800">Open dispatch board</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
            <button type="button"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    class="flex items-center gap-2 rounded-[8px] p-1 pr-2 hover:bg-neutral-100"
                    aria-label="Account menu for {{ $user['name'] }}">
                <x-avatar :name="$user['name']" size="sm" />
                <span class="hidden text-left lg:block">
                    <span class="block text-[13px] font-medium leading-tight text-neutral-800">{{ $user['name'] }}</span>
                    <span class="block text-[11px] leading-tight text-neutral-500">{{ $user['role'] }}</span>
                </span>
                <x-icon name="chevron-down" class="hidden text-neutral-400 lg:block" />
            </button>

            <div x-show="open"
                 x-cloak
                 x-transition.opacity.duration.150ms
                 @click.outside="open = false"
                 class="absolute right-0 z-50 mt-2 w-60 rounded-[12px] border border-neutral-200 bg-white p-1.5 shadow-flyout">
                <div class="border-b border-neutral-200 px-2.5 pb-2.5 pt-1.5">
                    <p class="text-sm font-medium text-neutral-800">{{ $user['name'] }}</p>
                    <p class="text-xs text-neutral-500" x-text="selectedDepot.name">{{ $user['branch'] }}</p>
                </div>
                @if(Rbac::allowsRoute($role, 'fleet.trips', 'view'))
                    <a href="{{ route('fleet.trips') }}" wire:navigate class="mt-1 flex items-center gap-2.5 rounded-[8px] px-2.5 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                        <x-icon name="user" class="text-neutral-500" /> My trips
                    </a>
                @endif
                @if(Rbac::allowsRoute($role, 'logistics.depots', 'view'))
                    <a href="{{ route('logistics.depots') }}" wire:navigate class="flex items-center gap-2.5 rounded-[8px] px-2.5 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                        <x-icon name="settings" class="text-neutral-500" /> Settings
                    </a>
                @endif
                <a href="{{ route('security.two-factor') }}" wire:navigate class="flex items-center justify-between gap-2.5 rounded-[8px] px-2.5 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                    <span class="flex items-center gap-2.5">
                        <x-icon name="shield-check" class="{{ auth()->user()?->hasTwoFactorEnabled() ? 'text-emerald-600' : 'text-neutral-400' }}" />
                        <span>Two-Factor Auth</span>
                    </span>
                    @if(auth()->user()?->hasTwoFactorEnabled())
                        <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800">Active</span>
                    @else
                        <span class="rounded-full bg-neutral-100 px-1.5 py-0.5 text-[10px] font-medium text-neutral-500">Off</span>
                    @endif
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-neutral-200 pt-1">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-[8px] px-2.5 py-2 text-sm text-danger hover:bg-[#FEECEA]">
                        <x-icon name="log-out" /> Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
