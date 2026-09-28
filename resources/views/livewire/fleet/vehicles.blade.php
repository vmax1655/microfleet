<div>
    <x-breadcrumb />

    <x-page-header
        title="Vehicle Registry"
        subtitle="Operational asset list for motorcycles, multi-cabs, vans, and utility vehicles used in MFI field work.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-vehicles')">Export</x-btn>
            @if($canCreateVehicles)
                <x-btn variant="primary" icon="truck" @click="$dispatch('open-modal', 'vehicle-entry')">New Vehicle</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Operational Asset KPIs --}}
    <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Available" :value="number_format($availableCount)" icon="check-circle" />
        <x-stat-card label="Reserved / Assigned" :value="number_format($reservedCount)" icon="calendar" />
        <x-stat-card label="In Maintenance" :value="number_format($maintenanceCount)" icon="wrench" />
        <x-stat-card label="Docs Due Soon" :value="number_format($docsDueCount)" icon="alert-triangle" />
    </section>

    {{-- ── Subaru-style Category Tab Bar ── --}}
    <div class="mt-8 border-b border-neutral-200">
        <div class="flex items-end justify-between">
            {{-- Category Tabs --}}
            <div class="flex items-end gap-0 overflow-x-auto scrollbar-none">
                {{-- All --}}
                <button type="button"
                        wire:click="setCategory('')"
                        class="relative shrink-0 px-5 pb-3 pt-1 text-sm font-semibold transition-colors duration-150
                               {{ $category === '' ? 'text-neutral-900' : 'text-neutral-400 hover:text-neutral-700' }}">
                    All
                    @if($category === '')
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-neutral-900"></span>
                    @endif
                    <span class="ml-1.5 rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">{{ $totalCount }}</span>
                </button>

                @foreach($categories as $cat)
                    @php $catCount = $categoryCounts[$cat->id] ?? 0; @endphp
                    <button type="button"
                            wire:click="setCategory('{{ $cat->name }}')"
                            class="relative shrink-0 px-5 pb-3 pt-1 text-sm font-semibold transition-colors duration-150
                                   {{ $category === $cat->name ? 'text-neutral-900' : 'text-neutral-400 hover:text-neutral-700' }}">
                        {{ $cat->name }}
                        @if($category === $cat->name)
                            <span class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-neutral-900"></span>
                        @endif
                        <span class="ml-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium
                                     {{ $category === $cat->name ? 'bg-primary-100 text-primary-700' : 'bg-neutral-100 text-neutral-500' }}">{{ $catCount }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Live Search --}}
            <div class="relative mb-2 w-64 shrink-0">
                <span class="pointer-events-none absolute inset-y-0 left-0 grid w-9 place-items-center text-neutral-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input type="search"
                       wire:model.live.debounce.250ms="search"
                       placeholder="Search plate, make, model…"
                       class="h-9 w-full rounded-[8px] border border-neutral-300 bg-white pl-9 pr-3 text-xs text-neutral-800 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30">
            </div>
        </div>
    </div>

    {{-- Active Filter Indicator --}}
    @if($category !== '' || $search !== '')
        <div class="mt-3 flex items-center justify-between text-xs text-neutral-500">
            <span>
                Filtering by:
                @if($category) <strong class="text-primary-700">{{ $category }}</strong> @endif
                @if($category && $search) &middot; @endif
                @if($search) <strong class="text-primary-700">"{{ $search }}"</strong> @endif
                — <span class="text-neutral-600">{{ $vehicles->count() }} result{{ $vehicles->count() === 1 ? '' : 's' }}</span>
            </span>
            <button type="button"
                    wire:click="$set('category', ''); $set('search', '')"
                    class="font-medium text-danger hover:underline">Clear filters</button>
        </div>
    @endif

    {{-- ── Vehicle Card Grid (Subaru layout) ── --}}
    <div class="mt-6">
        @if($vehicles->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-[16px] border-2 border-dashed border-neutral-200 bg-neutral-50 py-20 text-center">
                <x-icon name="truck" class="h-12 w-12 text-neutral-300 mb-3" />
                <p class="font-semibold text-neutral-600">No vehicles found</p>
                @if($category || $search)
                    <p class="mt-1 text-sm text-neutral-400">Try a different category or clear the search.</p>
                    <button type="button" wire:click="$set('category', ''); $set('search', '')"
                            class="mt-3 text-sm font-medium text-primary-700 hover:underline">Show all vehicles</button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach($vehicles as $row)
                    @php
                        $status      = str($row->status)->replace('_', ' ')->title()->value();
                        $statusColor = match($row->status) {
                            'available'         => 'bg-emerald-100 text-emerald-700',
                            'reserved','assigned'=> 'bg-amber-100 text-amber-700',
                            'under_maintenance' => 'bg-rose-100 text-rose-700',
                            default             => 'bg-neutral-100 text-neutral-600',
                        };
                        $categorySlug = strtolower(str_replace([' ', '-'], '_', $row->type?->name ?? 'vehicle'));
                    @endphp

                    <div class="group flex flex-col">
                        {{-- ── Photo area ── --}}
                        <button type="button"
                                wire:click="viewVehicle({{ $row->id }})"
                                @click="$dispatch('open-modal', 'vehicle-detail')"
                                class="relative w-full overflow-hidden rounded-[4px] bg-neutral-100 focus:outline-none">

                            @if($row->image_url)
                                <img src="{{ $row->image_url }}"
                                     alt="{{ $row->plate_number }}"
                                     class="h-44 w-full object-cover object-center transition-transform duration-300 group-hover:scale-[1.03]" />
                            @else
                                {{-- Real photo placeholder by category --}}
                                @php
                                    $defaultImg = match($categorySlug) {
                                        'motorcycle'     => asset('storage/vehicles/default-motorcycle.webp'),
                                        'multi_cab'      => asset('storage/vehicles/default-multi-cab.webp'),
                                        'passenger_van'  => asset('storage/vehicles/default-passenger-van.jpg'),
                                        'utility_pickup' => asset('storage/vehicles/default-utility-pickup.jpg'),
                                        default          => null,
                                    };
                                @endphp
                                @if($defaultImg)
                                    <img src="{{ $defaultImg }}"
                                         alt="{{ $row->type?->name }}"
                                         class="h-44 w-full object-cover object-center transition-transform duration-300 group-hover:scale-[1.03] opacity-80" />
                                @else
                                    <div class="flex h-44 w-full items-center justify-center bg-neutral-100 transition-transform duration-300 group-hover:scale-[1.03]">
                                        <x-icon name="truck" class="h-12 w-12 text-neutral-300" />
                                    </div>
                                @endif
                            @endif

                            {{-- Status pill overlay --}}
                            <span class="absolute top-2 right-2 rounded-full px-2.5 py-0.5 text-[11px] font-semibold shadow-sm {{ $statusColor }}">
                                {{ $status }}
                            </span>
                        </button>

                        {{-- ── Info area below photo ── --}}
                        <div class="mt-3 flex-1">
                            <h3 class="text-[15px] font-bold text-neutral-900 tracking-tight">
                                {{ $row->plate_number }}
                            </h3>
                            <p class="mt-0.5 text-[13px] text-neutral-500">
                                {{ $row->make ? $row->make.' '.$row->model : ($row->type?->name ?? ucfirst($row->fuel_type)) }}
                            </p>
                            <p class="mt-1 text-[12px] text-neutral-400">
                                {{ number_format((float) $row->current_odometer_km) }} km &middot; {{ $row->depot?->name ?? '—' }}
                            </p>
                            @php
                                $cardDl = match($categorySlug) {
                                    'motorcycle'     => 'Code A',
                                    'multi_cab'      => 'Code A1 / B',
                                    'passenger_van'  => 'Code B / B1',
                                    'utility_pickup' => 'Code B2 / C',
                                    default          => 'Code B',
                                };
                            @endphp
                            <div class="mt-2">
                                <span class="inline-flex items-center gap-1 rounded bg-neutral-100 px-2 py-0.5 text-[11px] font-bold text-neutral-700 border border-neutral-200">
                                    Req: {{ $cardDl }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Register Vehicle Modal with Image Upload ── --}}
    <x-modal name="vehicle-entry" title="Register Vehicle" subtitle="Create a dispatchable fleet asset with photo." icon="truck" size="lg">
        <form id="vehicle-entry-form" wire:submit="saveVehicle">
            <div class="space-y-4">
                {{-- Vehicle Image Upload with Live Preview --}}
                <div class="rounded-[10px] border border-neutral-200 bg-neutral-50/60 p-3.5">
                    <label class="block text-xs font-semibold text-neutral-700 mb-2">
                        Vehicle Image <span class="text-neutral-400 font-normal">(Optional, PNG / JPG / WebP up to 5MB)</span>
                    </label>
                    <div class="flex items-center gap-4">
                        @if ($image)
                            <div class="relative shrink-0">
                                <img src="{{ $image->temporaryUrl() }}"
                                     alt="Preview"
                                     class="h-24 w-36 rounded-[8px] object-cover border border-neutral-200 shadow-sm" />
                                <button type="button"
                                        wire:click="clearImage"
                                        title="Remove image"
                                        class="absolute -top-1.5 -right-1.5 grid h-5 w-5 place-items-center rounded-full bg-danger text-white text-xs hover:bg-danger/80 shadow">
                                    &times;
                                </button>
                            </div>
                        @else
                            <div class="grid h-24 w-36 shrink-0 place-items-center rounded-[8px] border-2 border-dashed border-neutral-300 bg-white text-neutral-400">
                                <x-icon name="camera" class="h-8 w-8 text-neutral-300" />
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <input type="file"
                                   id="vehicle-image-input"
                                   wire:model="image"
                                   accept="image/png,image/jpeg,image/webp,image/jpg"
                                   class="block w-full text-xs text-neutral-600 file:mr-3 file:rounded-[6px] file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-700 hover:file:bg-primary-100 cursor-pointer" />
                            <p class="mt-1 text-[11px] text-neutral-500">
                                Upload a clear front or side photo of the vehicle.
                            </p>
                            <div wire:loading wire:target="image" class="mt-1 flex items-center gap-1.5 text-xs text-accent-600">
                                <span class="animate-spin text-sm">&#9696;</span> Uploading image preview...
                            </div>
                            @error('image')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Form Fields Grid --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Plate number" name="plate" wire:model="plate" :error="$errors->first('plate')" required />
                    <x-form-field label="Category / Vehicle type" type="select" name="type" wire:model="type" :options="$typeOptions" :error="$errors->first('type')" required />
                    <x-form-field label="Make (brand)" name="make" wire:model="make" placeholder="e.g. Honda, Toyota" :error="$errors->first('make')" />
                    <x-form-field label="Model" name="vehicleModel" wire:model="vehicleModel" placeholder="e.g. Civic, Hilux" :error="$errors->first('vehicleModel')" />
                    <x-form-field label="Depot" type="select" name="depot" wire:model="depot" :options="$depotOptions" :error="$errors->first('depot')" required />
                    <x-form-field label="Fuel type" type="select" name="fuel" wire:model="fuel" :options="['gasoline' => 'Gasoline', 'diesel' => 'Diesel', 'hybrid' => 'Hybrid', 'electric' => 'Electric']" :error="$errors->first('fuel')" required />
                    <x-form-field label="Current odometer" type="number" name="odometer" wire:model="odometer" suffix="km" tabular :error="$errors->first('odometer')" required />
                    <x-form-field label="Status" type="select" name="status" wire:model="status" :options="['available' => 'Available', 'inactive' => 'Inactive']" :error="$errors->first('status')" required />
                </div>
            </div>
        </form>
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check" wire:click="saveVehicle" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveVehicle">Save Vehicle</span>
                <span wire:loading wire:target="saveVehicle">Saving...</span>
            </x-btn>
        </x-slot:footer>
    </x-modal>

    {{-- ── Vehicle Detail Modal with Full Photo Showcase ── --}}
    <x-modal name="vehicle-detail" title="Vehicle Details" subtitle="Asset profile, photo, and operational status." icon="truck" size="lg">
        @php
            $displayVehicle = $selectedVehicle ?? $vehicles->first();
        @endphp
        @if($displayVehicle)
            <div class="space-y-4">
                {{-- Vehicle Image Display --}}
                @if($displayVehicle->image_url)
                    <div class="relative overflow-hidden rounded-[12px] border border-neutral-200 bg-neutral-900 shadow-sm">
                        <img src="{{ $displayVehicle->image_url }}"
                             alt="{{ $displayVehicle->plate_number }}"
                             class="h-64 w-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                        <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between text-white">
                            <div>
                                <span class="text-xs font-medium uppercase tracking-wider text-accent-300">
                                    {{ $displayVehicle->type?->name ?? 'Vehicle' }}
                                </span>
                                <h3 class="text-2xl font-bold tracking-tight">{{ $displayVehicle->plate_number }}</h3>
                            </div>
                            <span class="rounded-full bg-black/60 backdrop-blur px-3 py-1 text-xs font-semibold text-white">
                                {{ $displayVehicle->make ? $displayVehicle->make.' '.$displayVehicle->model : 'Microfleet Asset' }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="flex h-36 w-full flex-col items-center justify-center rounded-[12px] border-2 border-dashed border-neutral-200 bg-neutral-50 text-neutral-400">
                        <x-icon name="truck" class="h-10 w-10 text-neutral-300 mb-1" />
                        <span class="text-xs text-neutral-500 font-medium">No vehicle photo uploaded yet</span>
                    </div>
                @endif

                {{-- Technical Specifications Grid --}}
                <div class="grid grid-cols-2 gap-3 rounded-[10px] border border-neutral-200 bg-neutral-50/60 p-4 text-xs sm:grid-cols-4">
                    <div>
                        <span class="text-neutral-500">Plate Number</span>
                        <p class="font-bold text-neutral-900 text-sm tabular-nums">{{ $displayVehicle->plate_number }}</p>
                    </div>
                    <div>
                        <span class="text-neutral-500">Category</span>
                        <p class="font-semibold text-neutral-800 text-sm">{{ $displayVehicle->type?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-neutral-500">Make / Model</span>
                        <p class="font-semibold text-neutral-800 text-sm">{{ $displayVehicle->make ? $displayVehicle->make.' '.$displayVehicle->model : '—' }}</p>
                    </div>
                    <div>
                        <span class="text-neutral-500">Depot Location</span>
                        <p class="font-semibold text-neutral-800 text-sm">{{ $displayVehicle->depot?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-neutral-500">Current Odometer</span>
                        <p class="font-semibold text-neutral-800 text-sm tabular-nums">{{ number_format((float) $displayVehicle->current_odometer_km) }} km</p>
                    </div>
                    <div>
                        <span class="text-neutral-500">Fuel Type</span>
                        <p class="font-semibold text-neutral-800 text-sm capitalize">{{ $displayVehicle->fuel_type }}</p>
                    </div>
                    <div>
                        <span class="text-neutral-500">Status</span>
                        <div class="mt-0.5"><x-status-badge :status="str($displayVehicle->status)->replace('_', ' ')->title()" /></div>
                    </div>
                    <div>
                        <span class="text-neutral-500">Required LTO License</span>
                        @php
                            $detailCatSlug = strtolower(str_replace([' ', '-'], '_', $displayVehicle->type?->name ?? 'vehicle'));
                            $reqDl = match($detailCatSlug) {
                                'motorcycle'     => 'Code A (Motorcycle)',
                                'multi_cab'      => 'Code A1 / B (Multi-cab)',
                                'passenger_van'  => 'Code B / B1 (Passenger Van)',
                                'utility_pickup' => 'Code B2 / C (Utility Pickup)',
                                default          => 'Code B',
                            };
                        @endphp
                        <p class="font-bold text-emerald-700 text-xs">{{ $reqDl }}</p>
                    </div>
                </div>

                {{-- Connected Operational Modules --}}
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Operations Shortcut</p>
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                        @if($canViewDispatch)
                            <x-btn icon="navigation" :href="route('fleet.dispatch')">Dispatch Board</x-btn>
                        @endif
                        @if($canViewFuel)
                            <x-btn icon="fuel" :href="route('logistics.fuel')">Fuel</x-btn>
                        @endif
                        @if($canViewMaintenance)
                            <x-btn icon="wrench" :href="route('logistics.maintenance')">Maintenance</x-btn>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="py-10 text-center text-sm text-neutral-500">
                <x-icon name="truck" class="mx-auto h-8 w-8 text-neutral-300 mb-2" />
                Select a vehicle to inspect details and photo.
            </div>
        @endif
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Close</x-btn>
        </x-slot:footer>
    </x-modal>

    {{-- Export Modal --}}
    <x-modal name="export-vehicles" title="Export Vehicle Registry" icon="download">
        <x-form-field label="Format" type="select" name="format" :options="['CSV', 'PDF']" />
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" @click="$dispatch('close-modal')">Download</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
