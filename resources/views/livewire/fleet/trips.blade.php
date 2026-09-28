<div>
    @php use App\Support\Format; @endphp

    <x-breadcrumb />

    {{-- Feedback Banner --}}
    @if($showBanner)
        <div class="{{ $bannerType === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800' }} border rounded-lg px-4 py-3 mb-4 flex items-start gap-3">
            <x-icon name="{{ $bannerType === 'success' ? 'check-circle' : 'x-circle' }}" class="w-5 h-5 shrink-0 mt-0.5" />
            <span class="text-sm flex-1">{{ $bannerMessage }}</span>
            <button wire:click="dismissBanner" class="ml-2 text-current opacity-60 hover:opacity-100">&times;</button>
        </div>
    @endif

    <x-page-header
        title="Trip Monitoring"
        subtitle="Odometer validation, field movement logs, cost reconciliation, and route preview.">
        <x-slot:actions>
            <x-btn icon="download" wire:click="exportCsv" wire:loading.attr="disabled">Export CSV</x-btn>
            @if($canEditTrips && $trips->where('status', 'in_transit')->isNotEmpty())
                <x-btn variant="primary" icon="route"
                    @click="$dispatch('open-modal', 'trip-checkin')"
                    wire:click="openCheckinModal">Check In Trip</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
        <x-stat-card label="In Transit" :value="number_format($trips->where('status', 'in_transit')->count())" icon="navigation" />
        <x-stat-card label="For Return Check-in" :value="number_format($trips->where('status', 'in_transit')->count())" icon="clipboard-check" />
        <x-stat-card label="Distance Today" :value="number_format((float) $trips->sum('distance_km')).' km'" icon="route" />
        <x-stat-card label="Cost / km" :value="Format::peso((float) $trips->pluck('transportCost.cost_per_km')->filter()->avg())" icon="calculator" />
    </section>

    <x-card class="mt-5" flush>
        <x-slot:title>Trip Ledger</x-slot:title>
        <x-slot:subtitle>Trips become financial records after ending odometer, fuel, and incidental expenses are reconciled.</x-slot:subtitle>

        <x-data-table sort-key="trip" caption="Trip monitoring ledger">
            <x-slot:head>
                <x-th sort="trip">Trip</x-th>
                <x-th sort="route">Route</x-th>
                <x-th sort="driver">Driver</x-th>
                <x-th sort="vehicle">Vehicle</x-th>
                <x-th sort="odo">Odometer</x-th>
                <x-th sort="distance">Distance</x-th>
                <x-th sort="cost" align="right">Trip Cost</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>

            @forelse($trips as $i => $row)
                @php
                    $status = str($row->status)->replace('_', ' ')->title();
                    $odo = number_format((float) $row->start_odometer_km).' -> '.($row->end_odometer_km ? number_format((float) $row->end_odometer_km) : 'open');
                    
                    $originLat = $row->route?->depot?->latitude ?? 14.8527390;
                    $originLng = $row->route?->depot?->longitude ?? 120.8160380;
                    $destLat = $row->route?->destination_latitude ?? 14.9582210;
                    $destLng = $row->route?->destination_longitude ?? 120.9788270;
                @endphp
                <tr data-row data-trip="{{ $row->trip_number }}" data-route="{{ $row->route?->name }}" data-driver="{{ $row->driver?->user?->name }}" data-vehicle="{{ $row->vehicle?->plate_number }}" data-odo="{{ $odo }}" data-distance="{{ $row->distance_km }}" data-cost="{{ $row->transportCost?->total_cost }}" data-status="{{ $status }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td data-label="Trip" class="px-3 py-2.5 font-medium tabular-nums text-neutral-800">{{ $row->trip_number }}</td>
                    <td data-label="Route" class="px-3 py-2.5 text-neutral-700">{{ $row->route?->name ?? '-' }}</td>
                    <td data-label="Driver" class="px-3 py-2.5 text-neutral-600">{{ $row->driver?->user?->name ?? '-' }}</td>
                    <td data-label="Vehicle" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $row->vehicle?->plate_number ?? '-' }}</td>
                    <td data-label="Odometer" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $odo }}</td>
                    <td data-label="Distance" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $row->distance_km ? number_format((float) $row->distance_km, 1).' km' : '-' }}</td>
                    <td data-label="Trip Cost" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ $row->transportCost ? Format::peso($row->transportCost->total_cost) : '-' }}</td>
                    <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$status" /></td>
                    <td data-label="" class="px-3 py-2.5 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            @if($canEditTrips && $row->status === 'in_transit')
                                <x-btn size="sm"
                                    variant="primary"
                                    icon="check"
                                    @click="$dispatch('open-modal', 'trip-checkin')"
                                    wire:click="openCheckinModal('{{ $row->trip_number }}')">Check In</x-btn>
                            @endif
                            <x-btn size="sm" icon="map-pin" @click="$dispatch('open-modal', 'route-map'); $dispatch('load-route-trip', {
                                trip: '{{ $row->trip_number }}',
                                route: '{{ e($row->route?->name ?? 'Standard Route') }}',
                                driver: '{{ e($row->driver?->user?->name ?? 'Unassigned Driver') }}',
                                vehicle: '{{ e($row->vehicle?->plate_number ?? 'N/A') }}',
                                distance: '{{ $row->route?->planned_distance_km ?? $row->distance_km ?? '0' }} km',
                                duration: '{{ $row->route?->estimated_duration_minutes ?? '0' }} min',
                                profile: '{{ $row->route?->road_profile ?? 'Mixed' }}',
                                status: '{{ $status }}',
                                originName: '{{ e($row->route?->depot?->name ?? 'Malolos Main Depot') }}',
                                originAddress: '{{ e($row->route?->depot?->address ?? 'Malolos, Bulacan') }}',
                                originLat: {{ $originLat }},
                                originLng: {{ $originLng }},
                                destName: '{{ e($row->route?->destination_name ?? $row->route?->name ?? 'Destination Center') }}',
                                destCenter: '{{ e($row->route?->center_code ?? 'CTR') }}',
                                destLat: {{ $destLat }},
                                destLng: {{ $destLng }}
                            })">Route Map</x-btn>
                            @if($canViewCosts)
                                <x-btn size="sm" icon="calculator" :href="route('intelligence.costs')">Cost</x-btn>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-3 py-8 text-center text-sm text-neutral-500">No trips yet.</td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    <x-modal name="route-map" title="Route Preview Map" subtitle="Point A to Point B route overview" icon="map-pin" size="xl">
        <div x-data="routeMapComponent" x-on:load-route-trip.window="activeTrip = $event.detail; drawLeaflet();" class="space-y-3">
            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                <div class="flex items-center justify-between rounded-[8px] border border-emerald-300 bg-emerald-50/90 px-3.5 py-2 shadow-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 font-black text-white text-xs shadow">A</span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-900">POINT A (ORIGIN DEPOT)</p>
                            <p class="text-xs font-bold text-neutral-900 truncate" x-text="activeTrip?.originName || 'Malolos Main Depot'"></p>
                        </div>
                    </div>
                    <span class="text-[11px] font-mono text-emerald-800 font-semibold tabular-nums ml-2 shrink-0" x-text="(activeTrip?.originLat || '14.8527390') + ', ' + (activeTrip?.originLng || '120.8160380')"></span>
                </div>

                <div class="flex items-center justify-between rounded-[8px] border border-rose-300 bg-rose-50/90 px-3.5 py-2 shadow-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-600 font-black text-white text-xs shadow">B</span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-rose-900">POINT B (DESTINATION)</p>
                            <p class="text-xs font-bold text-neutral-900 truncate" x-text="activeTrip?.destName || 'Destination Center'"></p>
                        </div>
                    </div>
                    <span class="text-[11px] font-mono text-rose-800 font-semibold tabular-nums ml-2 shrink-0" x-text="(activeTrip?.destLat || '14.9582210') + ', ' + (activeTrip?.destLng || '120.9788270')"></span>
                </div>
            </div>

            <div id="trip-route-map-container" class="h-[360px] w-full rounded-[10px] border-2 border-neutral-300 shadow-md z-0 bg-neutral-100" style="min-height: 360px;"></div>

            <div class="grid grid-cols-2 gap-2 rounded-[8px] bg-neutral-100 p-2.5 text-xs sm:grid-cols-4">
                <div>
                    <span class="text-neutral-500 font-medium">Planned Distance</span>
                    <p class="font-bold tabular-nums text-neutral-900" x-text="activeTrip?.distance || '-'"></p>
                </div>
                <div>
                    <span class="text-neutral-500 font-medium">Est. Duration</span>
                    <p class="font-bold tabular-nums text-neutral-900" x-text="activeTrip?.duration || '-'"></p>
                </div>
                <div>
                    <span class="text-neutral-500 font-medium">Assigned Driver</span>
                    <p class="font-bold text-neutral-900" x-text="activeTrip?.driver || '-'"></p>
                </div>
                <div>
                    <span class="text-neutral-500 font-medium">Vehicle Plate</span>
                    <p class="font-bold tabular-nums text-neutral-900" x-text="activeTrip?.vehicle || '-'"></p>
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-btn variant="primary" @click="$dispatch('close-modal', 'route-map')">Close Preview</x-btn>
        </x-slot:footer>
    </x-modal>

    {{-- Return Check-in Modal --}}
    <x-modal name="trip-checkin" title="Return Check-in" subtitle="Record ending odometer and validate return condition." icon="route">
        <form id="trip-checkin-form" wire:submit.prevent="checkInTrip">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form-field
                        label="Trip"
                        type="select"
                        name="trip"
                        wire:model.live="trip"
                        :options="$tripOptions"
                        :error="$errors->first('trip')"
                        required
                    />
                </div>

                {{-- Live Trip Info Badge --}}
                @if($start_odometer > 0)
                    <div class="sm:col-span-2 rounded-lg bg-primary-50 border border-primary-200 p-3 text-xs text-primary-900 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="text-primary-700">Vehicle:</span>
                            <strong>{{ $selectedVehicle ?? 'Assigned' }}</strong>
                            <span class="mx-1 text-primary-300">|</span>
                            <span class="text-primary-700">Driver:</span>
                            <strong>{{ $selectedDriver ?? 'Assigned' }}</strong>
                        </div>
                        <div>
                            <span class="text-primary-700">Start Odometer:</span>
                            <strong class="tabular-nums">{{ number_format($start_odometer) }} km</strong>
                            <span class="mx-1 text-primary-300">|</span>
                            <span class="text-primary-700">Planned:</span>
                            <strong class="tabular-nums">{{ number_format($planned_distance, 1) }} km</strong>
                        </div>
                    </div>
                @endif

                <div>
                    <x-form-field
                        label="Ending odometer"
                        type="number"
                        step="0.1"
                        name="end_odometer"
                        wire:model.live="end_odometer"
                        suffix="km"
                        :error="$errors->first('end_odometer')"
                        required
                        tabular
                    />
                    @php
                        $computedDist = is_numeric($end_odometer) ? round((float)$end_odometer - $start_odometer, 2) : 0;
                    @endphp
                    <div class="mt-1 text-xs {{ $computedDist >= 0 ? 'text-emerald-700' : 'text-danger' }}">
                        @if($computedDist >= 0)
                            Calculated Distance: <strong class="tabular-nums">{{ number_format($computedDist, 1) }} km</strong>
                        @else
                            <span class="font-medium">Must be &ge; {{ number_format($start_odometer) }} km (start odometer)</span>
                        @endif
                    </div>
                </div>

                <div>
                    <x-form-field
                        label="Return time"
                        type="datetime-local"
                        name="returned_at"
                        wire:model="returned_at"
                        :error="$errors->first('returned_at')"
                        required
                    />
                </div>

                <div class="sm:col-span-2">
                    <x-form-field
                        label="Vehicle condition"
                        type="select"
                        name="condition"
                        wire:model="condition"
                        :options="[
                            'Passed' => 'Passed - In good condition, ready for service',
                            'Needs Maintenance' => 'Needs Maintenance - Minor issues noted, queue for inspection',
                            'Incident Reported' => 'Incident Reported - Critical defect or incident occurred'
                        ]"
                        :error="$errors->first('condition')"
                        required
                    />
                </div>

                <div class="sm:col-span-2">
                    <x-form-field
                        label="Return notes"
                        type="textarea"
                        name="notes"
                        wire:model="notes"
                        rows="2"
                        placeholder="Optional notes regarding vehicle condition, route delays, or handover..."
                        :error="$errors->first('notes')"
                    />
                </div>
            </div>
        </form>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal', 'trip-checkin')">Cancel</x-btn>
            <x-btn icon="fuel" :href="route('logistics.fuel')">Log Fuel</x-btn>
            <x-btn variant="primary" icon="check"
                wire:click="checkInTrip"
                wire:loading.attr="disabled"
                wire:target="checkInTrip">
                <span wire:loading.remove wire:target="checkInTrip">Complete Trip</span>
                <span wire:loading wire:target="checkInTrip">Processing…</span>
            </x-btn>
        </x-slot:footer>
    </x-modal>

    <x-modal name="export-trips" title="Export Trip Ledger" icon="download">
        <p class="text-sm text-neutral-600 mb-4">Export all completed and in-transit trips along with odometer readings, distance, and transport costs.</p>
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal', 'export-trips')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" wire:click="exportCsv" @click="$dispatch('close-modal', 'export-trips')">Download CSV</x-btn>
        </x-slot:footer>
    </x-modal>
</div>

@script
<script>
    Alpine.data('routeMapComponent', () => ({
        activeTrip: null,
        map: null,

        drawLeaflet() {
            setTimeout(() => {
                const mapEl = document.getElementById('trip-route-map-container');
                if (!mapEl) return;

                if (this.map) {
                    this.map.remove();
                    this.map = null;
                }

                if (typeof L === 'undefined') return;

                const originLat = parseFloat(this.activeTrip?.originLat) || 14.8527390;
                const originLng = parseFloat(this.activeTrip?.originLng) || 120.8160380;
                const destLat = parseFloat(this.activeTrip?.destLat) || 14.9582210;
                const destLng = parseFloat(this.activeTrip?.destLng) || 120.9788270;

                this.map = L.map(mapEl, {
                    zoomControl: true,
                    scrollWheelZoom: true
                }).setView([originLat, originLng], 11);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(this.map);

                const markerA = L.circleMarker([originLat, originLng], {
                    radius: 12,
                    fillColor: '#059669',
                    color: '#ffffff',
                    weight: 3,
                    opacity: 1,
                    fillOpacity: 1
                }).addTo(this.map);

                markerA.bindTooltip(`
                    <div style="font-family:sans-serif;padding:3px 6px;">
                        <strong style="color:#047857;font-size:12px;display:block;">POINT A: ORIGIN DEPOT</strong>
                        <span style="font-weight:bold;font-size:13px;color:#111827;">${this.activeTrip?.originName || 'Malolos Main Depot'}</span>
                    </div>
                `, { permanent: true, direction: 'top', offset: [0, -12] }).openTooltip();

                const markerB = L.circleMarker([destLat, destLng], {
                    radius: 12,
                    fillColor: '#e11d48',
                    color: '#ffffff',
                    weight: 3,
                    opacity: 1,
                    fillOpacity: 1
                }).addTo(this.map);

                markerB.bindTooltip(`
                    <div style="font-family:sans-serif;padding:3px 6px;">
                        <strong style="color:#be123c;font-size:12px;display:block;">POINT B: DESTINATION</strong>
                        <span style="font-weight:bold;font-size:13px;color:#111827;">${this.activeTrip?.destName || 'Destination Center'}</span>
                    </div>
                `, { permanent: true, direction: 'top', offset: [0, -12] }).openTooltip();

                const polyline = L.polyline([
                    [originLat, originLng],
                    [destLat, destLng]
                ], {
                    color: '#2563eb',
                    weight: 5,
                    opacity: 0.9,
                    dashArray: '8, 8'
                }).addTo(this.map);

                this.map.fitBounds(polyline.getBounds(), { padding: [60, 60] });

                setTimeout(() => {
                    if (this.map) this.map.invalidateSize();
                }, 250);
            }, 250);
        }
    }));
</script>
@endscript
