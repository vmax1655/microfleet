@php
    $lanes = [
        'Approved' => $approvedReservations->map(fn ($reservation) => [
            'code' => $reservation->reservation_number,
            'route' => $reservation->route?->destination_name ?? $reservation->route?->name ?? 'Field Destination',
            'driver' => 'Needs assignment',
            'vehicle' => ($reservation->vehicleType?->name ?? 'Fleet') . ' class',
            'time' => $reservation->scheduled_start_at?->format('g:i A'),
            'type' => 'reservation',
        ]),
        'Assigned' => $dispatches->where('status', 'assigned')->map(fn ($dispatch) => [
            'code' => $dispatch->dispatch_number,
            'route' => $dispatch->reservation?->route?->destination_name ?? $dispatch->reservation?->route?->name ?? 'Field Route',
            'driver' => $dispatch->driver?->user?->name ?? 'Assigned Driver',
            'vehicle' => $dispatch->vehicle?->plate_number ?? 'Vehicle',
            'time' => optional($dispatch->assigned_at)->format('g:i A'),
            'type' => 'assigned',
        ]),
        'In Transit' => $dispatches->where('status', 'in_transit')->map(fn ($dispatch) => [
            'code' => $dispatch->dispatch_number,
            'route' => $dispatch->reservation?->route?->destination_name ?? $dispatch->reservation?->route?->name ?? 'En Route',
            'driver' => $dispatch->driver?->user?->name ?? 'Driver on Duty',
            'vehicle' => $dispatch->vehicle?->plate_number ?? 'Vehicle',
            'time' => optional($dispatch->checked_out_at)->format('g:i A'),
            'type' => 'in_transit',
        ]),
        'Returned' => $dispatches->where('status', 'completed')->map(fn ($dispatch) => [
            'code' => $dispatch->dispatch_number,
            'route' => $dispatch->reservation?->route?->destination_name ?? $dispatch->reservation?->route?->name ?? 'Trip Closed',
            'driver' => $dispatch->driver?->user?->name ?? 'Driver',
            'vehicle' => $dispatch->vehicle?->plate_number ?? 'Vehicle',
            'time' => 'Returned',
            'type' => 'completed',
        ]),
    ];
@endphp

<div>
    <x-breadcrumb />

    {{-- Banner feedback --}}
    @if($bannerMessage)
        <div class="mb-4 flex items-center gap-3 rounded-[10px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-xs"
             x-data x-init="setTimeout(() => $el.remove(), 7000)">
            <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-emerald-600" />
            {{ $bannerMessage }}
        </div>
    @endif

    <x-page-header
        title="Dispatch Board"
        subtitle="Gate checkout, driver assignment, in-transit monitoring, and return odometer control.">
        <x-slot:actions>
            <x-btn icon="refresh-cw" :href="route('fleet.dispatch')">Refresh</x-btn>
            @if($canAssignDispatch)
                <x-btn icon="user-check" @click="$dispatch('open-modal', 'dispatch-assignment')">Assign Dispatch</x-btn>
            @endif
            @if($canGateCheckout)
                <x-btn variant="primary" icon="navigation" @click="$dispatch('open-modal', 'gate-checkout')">Gate Checkout</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Kanban Board: 4 Operational Swimlanes --}}
    <section class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-4">
        @foreach($lanes as $status => $items)
            <x-card padding="p-4">
                <div class="mb-3 flex items-center justify-between gap-2 border-b border-neutral-100 pb-2">
                    <div class="flex items-center gap-2">
                        <h2 class="text-[14px] font-bold text-neutral-800">{{ $status }}</h2>
                        <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-600">{{ count($items) }}</span>
                    </div>
                    <x-status-badge :status="$status" />
                </div>

                <div class="space-y-3">
                    @forelse($items as $item)
                        <article class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-3 shadow-2xs hover:border-primary-300 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-bold tabular-nums text-neutral-900 text-sm">{{ $item['code'] }}</p>
                                <p class="text-xs tabular-nums text-neutral-500 bg-white px-1.5 py-0.5 rounded border border-neutral-200">{{ $item['time'] }}</p>
                            </div>
                            <p class="mt-1 text-[13px] text-neutral-600 font-medium truncate">{{ $item['route'] }}</p>
                            <dl class="mt-2.5 grid grid-cols-2 gap-2 text-xs bg-white p-2 rounded border border-neutral-100">
                                <div>
                                    <dt class="text-neutral-400 text-[10px] uppercase font-semibold">Driver</dt>
                                    <dd class="mt-0.5 font-medium text-neutral-800 truncate">{{ $item['driver'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-neutral-400 text-[10px] uppercase font-semibold">Vehicle</dt>
                                    <dd class="mt-0.5 font-medium text-neutral-800 truncate">{{ $item['vehicle'] }}</dd>
                                </div>
                            </dl>
                            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                {{-- Stage-specific primary action button --}}
                                @if($item['type'] === 'reservation' && $canAssignDispatch)
                                    <x-btn size="xs" variant="primary" icon="user-check"
                                        wire:click="openAssignModal('{{ $item['code'] }}')"
                                        @click="$dispatch('open-modal', 'dispatch-assignment')">Assign</x-btn>
                                @elseif($item['type'] === 'assigned' && $canGateCheckout)
                                    <x-btn size="xs" variant="primary" icon="navigation"
                                        wire:click="openCheckoutModal('{{ $item['code'] }}')"
                                        @click="$dispatch('open-modal', 'gate-checkout')">Checkout</x-btn>
                                @elseif($item['type'] === 'in_transit' && $canGateCheckout)
                                    <x-btn size="xs" variant="primary" icon="check-circle"
                                        wire:click="openCheckinModal('{{ $item['code'] }}')"
                                        @click="$dispatch('open-modal', 'gate-checkin')">Check-in</x-btn>
                                @endif

                                <x-btn size="xs" icon="eye"
                                    wire:click="viewDispatch('{{ $item['code'] }}')"
                                    @click="$dispatch('open-modal', 'dispatch-detail')">Details</x-btn>

                                @if($canViewTrips && $item['type'] !== 'reservation')
                                    <x-btn size="xs" icon="route" :href="route('fleet.trips')">Trip</x-btn>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-[8px] border border-dashed border-neutral-200 px-3 py-6 text-center text-xs text-neutral-400">No {{ strtolower($status) }} fleet items.</p>
                    @endforelse
                </div>
            </x-card>
        @endforeach
    </section>

    {{-- Gate Control Checklist Card --}}
    <x-card class="mt-5" title="Gate Control Checklist" subtitle="Required operational checkpoints before a vehicle can leave or close a field trip">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-kpi label="Starting odometer" value="Required" />
            <x-kpi label="Pre-trip check" value="Required" />
            <x-kpi label="Driver license" value="Valid" />
            <x-kpi label="Return odometer" value="Validated" />
        </div>
    </x-card>

    {{-- ── 1. Assign Vehicle and Driver Modal ── --}}
    @if($canAssignDispatch)
        <x-modal name="dispatch-assignment" title="Assign Vehicle and Driver" subtitle="Convert an approved reservation into an active dispatch." icon="user-check" size="lg">
            <form id="dispatch-assignment-form" wire:submit.prevent="assignDispatch" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Approved reservation" type="select" name="reservation" wire:model.live="reservation"
                        :options="$reservationOptions ?: ['' => '— No approved reservations —']"
                        :error="$errors->first('reservation')" required />

                    <x-form-field label="Available vehicle" type="select" name="vehicle" wire:model="vehicle"
                        :options="$vehicleOptions ?: ['' => '— No available vehicles —']"
                        :error="$errors->first('vehicle')" required />

                    <x-form-field label="Available driver" type="select" name="driver" wire:model="driver"
                        :options="$driverOptions ?: ['' => '— No available drivers —']"
                        :error="$errors->first('driver')" required />

                    <x-form-field label="Assigned time" type="datetime-local" name="assigned_at" wire:model="assigned_at"
                        :error="$errors->first('assigned_at')" required />
                </div>
            </form>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="check" wire:click="assignDispatch" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="assignDispatch">Create Dispatch</span>
                    <span wire:loading wire:target="assignDispatch">Creating...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- ── 2. Gate Checkout Modal ── --}}
    @if($canGateCheckout)
        <x-modal name="gate-checkout" title="Gate Checkout" subtitle="Release assigned vehicle from depot into transit." icon="navigation">
            <form id="gate-checkout-form" wire:submit.prevent="gateCheckout" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Dispatch" type="select" name="dispatch" wire:model="dispatch"
                        :options="$dispatchOptions ?: ['' => '— No assigned dispatches —']"
                        :error="$errors->first('dispatch')" required />

                    <x-form-field label="Starting odometer" type="number" step="any" name="odometer" wire:model="odometer" suffix="km"
                        :error="$errors->first('odometer')" required tabular />

                    <x-form-field label="Vehicle condition" type="select" name="condition" wire:model="condition"
                        :options="['Passed' => 'Passed (Cleared)', 'Needs Review' => 'Needs Review', 'Failed' => 'Failed (Block)']"
                        :error="$errors->first('condition')" required />

                    <x-form-field label="Checkout time" type="datetime-local" name="time" wire:model="time"
                        :error="$errors->first('time')" required />
                </div>
            </form>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="navigation" wire:click="gateCheckout" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="gateCheckout">Dispatch Vehicle</span>
                    <span wire:loading wire:target="gateCheckout">Checking out...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- ── 3. Gate Return / Check-in Modal ── --}}
    @if($canGateCheckout)
        <x-modal name="gate-checkin" title="Gate Return / Check-in" subtitle="Record return odometer and mark trip as completed." icon="check-circle">
            <form id="gate-checkin-form" wire:submit.prevent="gateCheckin" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="In-Transit Dispatch" type="select" name="checkin_dispatch" wire:model="checkin_dispatch"
                        :options="$inTransitDispatchOptions ?: ['' => '— No active in-transit dispatches —']"
                        :error="$errors->first('checkin_dispatch')" required />

                    <x-form-field label="Return Odometer" type="number" step="any" name="checkin_odometer" wire:model="checkin_odometer" suffix="km"
                        :error="$errors->first('checkin_odometer')" required tabular />

                    <x-form-field label="Return Condition" type="select" name="checkin_condition" wire:model="checkin_condition"
                        :options="['Good / Clean' => 'Good / Clean', 'Minor Maintenance Needed' => 'Minor Maintenance Needed', 'Major Service Required' => 'Major Service Required (To Maintenance)']"
                        :error="$errors->first('checkin_condition')" required />

                    <x-form-field label="Return Time" type="datetime-local" name="checkin_time" wire:model="checkin_time"
                        :error="$errors->first('checkin_time')" required />
                </div>

                <x-form-field label="Return Notes / Observations" type="textarea" name="checkin_notes" wire:model="checkin_notes" rows="2"
                    :error="$errors->first('checkin_notes')" placeholder="Optional notes on vehicle state or trip..." />
            </form>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="check-circle" wire:click="gateCheckin" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="gateCheckin">Complete Check-in</span>
                    <span wire:loading wire:target="gateCheckin">Checking in...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- ── 4. Dispatch / Reservation Details Modal ── --}}
    <x-modal name="dispatch-detail" title="Dispatch & Trip Details" subtitle="Operational profile and tracking overview." icon="navigation" size="lg">
        @if($selectedDispatch)
            <div class="space-y-4">
                <div class="flex items-center justify-between rounded-[8px] bg-primary-50 p-3 border border-primary-100">
                    <div>
                        <span class="text-xs uppercase font-bold text-primary-700 tracking-wider">Dispatch Number</span>
                        <h3 class="text-base font-bold text-primary-950">{{ $selectedDispatch->dispatch_number }}</h3>
                    </div>
                    <x-status-badge :status="ucwords(str_replace('_', ' ', $selectedDispatch->status))" />
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs">
                    <div class="rounded-[8px] border border-neutral-200 bg-neutral-50/60 p-3 space-y-1.5">
                        <span class="font-bold text-neutral-800 text-[11px] uppercase tracking-wider block">Vehicle Profile</span>
                        <div class="flex justify-between"><span class="text-neutral-500">Plate:</span> <strong class="text-neutral-900">{{ $selectedDispatch->vehicle?->plate_number }}</strong></div>
                        <div class="flex justify-between"><span class="text-neutral-500">Model:</span> <span class="text-neutral-700">{{ $selectedDispatch->vehicle?->make }} {{ $selectedDispatch->vehicle?->model }}</span></div>
                        <div class="flex justify-between"><span class="text-neutral-500">Class:</span> <span class="text-neutral-700">{{ $selectedDispatch->vehicle?->type?->name }}</span></div>
                        <div class="flex justify-between"><span class="text-neutral-500">Odometer:</span> <span class="text-neutral-700 font-mono">{{ number_format((float) $selectedDispatch->vehicle?->current_odometer_km) }} km</span></div>
                    </div>

                    <div class="rounded-[8px] border border-neutral-200 bg-neutral-50/60 p-3 space-y-1.5">
                        <span class="font-bold text-neutral-800 text-[11px] uppercase tracking-wider block">Assigned Driver</span>
                        <div class="flex justify-between"><span class="text-neutral-500">Name:</span> <strong class="text-neutral-900">{{ $selectedDispatch->driver?->user?->name }}</strong></div>
                        <div class="flex justify-between"><span class="text-neutral-500">License:</span> <span class="text-neutral-700 font-mono">{{ $selectedDispatch->driver?->license_number }}</span></div>
                        <div class="flex justify-between"><span class="text-neutral-500">Restrictions:</span> <span class="text-neutral-700">{{ $selectedDispatch->driver?->license_restrictions }}</span></div>
                        <div class="flex justify-between"><span class="text-neutral-500">Phone:</span> <span class="text-neutral-700 font-mono">{{ $selectedDispatch->driver?->phone ?? 'N/A' }}</span></div>
                    </div>
                </div>

                <div class="rounded-[8px] border border-neutral-200 p-3 text-xs space-y-1">
                    <span class="font-bold text-neutral-800 text-[11px] uppercase tracking-wider block">Reservation & Destination</span>
                    <div class="flex justify-between"><span class="text-neutral-500">Reservation:</span> <span class="font-medium text-neutral-800">{{ $selectedDispatch->reservation?->reservation_number }}</span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">Purpose:</span> <span class="text-neutral-700">{{ $selectedDispatch->reservation?->purpose }}</span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">Destination:</span> <span class="text-neutral-700">{{ $selectedDispatch->reservation?->route?->destination_name ?? $selectedDispatch->reservation?->route?->name }}</span></div>
                </div>
            </div>
        @elseif($selectedReservation)
            <div class="space-y-4 text-xs">
                <div class="flex items-center justify-between rounded-[8px] bg-neutral-100 p-3">
                    <div>
                        <span class="text-[11px] uppercase font-bold text-neutral-500">Reservation</span>
                        <h3 class="text-base font-bold text-neutral-800">{{ $selectedReservation->reservation_number }}</h3>
                    </div>
                    <x-status-badge :status="ucwords(str_replace('_', ' ', $selectedReservation->status))" />
                </div>
                <div class="rounded-[8px] border border-neutral-200 p-3 space-y-1.5">
                    <div class="flex justify-between"><span class="text-neutral-500">Purpose:</span> <strong class="text-neutral-800">{{ $selectedReservation->purpose }}</strong></div>
                    <div class="flex justify-between"><span class="text-neutral-500">Requester:</span> <span class="text-neutral-700">{{ $selectedReservation->requester?->name }}</span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">Destination:</span> <span class="text-neutral-700">{{ $selectedReservation->route?->destination_name ?? $selectedReservation->route?->name }}</span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">Requested Class:</span> <span class="text-neutral-700">{{ $selectedReservation->vehicleType?->name }}</span></div>
                </div>
            </div>
        @else
            <p class="text-xs text-neutral-500 text-center py-4">No dispatch selected.</p>
        @endif

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Close</x-btn>
            @if($canViewTrips)
                <x-btn icon="route" :href="route('fleet.trips')">Trip Log</x-btn>
            @endif
            @if($canViewRoutes)
                <x-btn icon="map-pin" :href="route('logistics.routes')">Routes</x-btn>
            @endif
            @if($canViewFuel)
                <x-btn icon="fuel" :href="route('logistics.fuel')">Fuel Receipts</x-btn>
            @endif
        </x-slot:footer>
    </x-modal>
</div>
