<div>
    <x-breadcrumb />

    <x-page-header title="Depots and Staging Yards" subtitle="Centralized regional map, staging yards, and fleet allocation overview.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-depots')">Export</x-btn>
            @if($canCreateDepot)
                <x-btn variant="primary" icon="building-2" @click="$dispatch('open-modal', 'depot-entry')">New Depot</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if($bannerMessage)
        <div class="mt-4 flex items-center justify-between rounded-[8px] border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="alert">
            <div class="flex items-center gap-2">
                <x-icon name="check-circle" class="h-4 w-4 text-emerald-600" />
                <span>{{ $bannerMessage }}</span>
            </div>
            <button type="button" wire:click="$set('bannerMessage', null)" class="text-emerald-700 hover:text-emerald-900">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif

    <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
        <x-stat-card label="Total Depots & Yards" :value="number_format($depots->count())" icon="building-2" />
        <x-stat-card label="Total Staged Fleet" :value="number_format($depots->sum('total_vehicles'))" icon="truck" />
        <x-stat-card label="Available Across Depots" :value="number_format($depots->sum('available'))" icon="check-circle" />
        <x-stat-card label="Active Dispatches" :value="number_format($depots->sum('dispatched'))" icon="navigation" />
    </section>

    <!-- Central Regional Leaflet Map Dashboard Card -->
    <x-card class="mt-6" flush>
        <x-slot:title>Multi-Depot Fleet Allocation Map</x-slot:title>
        <x-slot:subtitle>Live regional staging map across Bulacan showing depot location pins, available assets, and vehicle categories.</x-slot:subtitle>

        <div x-data="depotsMapComponent(@js($depots))" class="p-4 space-y-4">
            <!-- Map Canvas Container -->
            <div id="multi-depot-overview-map" class="h-[420px] w-full rounded-[10px] border-2 border-neutral-300 shadow-md z-0 bg-neutral-100" style="min-height: 420px;"></div>

            <!-- Depot Staging KPI Cards Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach($depots as $depot)
                    <div class="flex flex-col justify-between rounded-[10px] border border-neutral-200 bg-neutral-50/80 p-4 transition-shadow hover:shadow-md">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">
                                        {{ $depot['status'] }}
                                    </span>
                                    <h3 class="mt-2 text-base font-bold text-neutral-900">{{ $depot['name'] }}</h3>
                                    <p class="text-xs text-neutral-500 font-medium">{{ $depot['address'] }}</p>
                                </div>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 font-bold text-primary-800 text-xs">
                                    {{ $depot['total_vehicles'] }}
                                </span>
                            </div>

                            <dl class="mt-4 grid grid-cols-3 gap-2 rounded-[8px] bg-white p-2.5 text-center text-xs border border-neutral-200">
                                <div>
                                    <dt class="text-neutral-500 font-medium">Available</dt>
                                    <dd class="mt-0.5 font-bold tabular-nums text-emerald-700 text-sm">{{ $depot['available'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-neutral-500 font-medium">Dispatched</dt>
                                    <dd class="mt-0.5 font-bold tabular-nums text-primary-700 text-sm">{{ $depot['dispatched'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-neutral-500 font-medium">Maintenance</dt>
                                    <dd class="mt-0.5 font-bold tabular-nums text-rose-700 text-sm">{{ $depot['maintenance'] }}</dd>
                                </div>
                            </dl>

                            <!-- Vehicle Types breakdown -->
                            @if(!empty($depot['by_type']))
                                <div class="mt-3 flex flex-wrap gap-1">
                                    @foreach($depot['by_type'] as $type => $count)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-neutral-200/80 px-2 py-0.5 text-[11px] font-bold text-neutral-800">
                                            {{ $type }}: {{ $count }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-neutral-200/80 flex items-center justify-between">
                            <span class="text-[11px] font-mono text-neutral-500 tabular-nums">{{ $depot['lat'] }}, {{ $depot['lng'] }}</span>
                            <x-btn size="sm" icon="map-pin" @click="focusDepot({{ $depot['id'] }})">Focus Map</x-btn>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-card>

    <x-card class="mt-6" flush>
        <x-slot:title>Depot Directory & Staging Stacks</x-slot:title>
        <x-slot:subtitle>Depots define where vehicles are staged, dispatched, and returned across the regional network.</x-slot:subtitle>
        <x-data-table sort-key="name" caption="Depot directory">
            <x-slot:head>
                <x-th sort="name">Depot</x-th>
                <x-th sort="city">Location & Coordinates</x-th>
                <x-th sort="vehicles" align="right">Total Fleet</x-th>
                <x-th sort="active" align="right">Available</x-th>
                <x-th sort="dispatched" align="right">Dispatched</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>
            @foreach($depots as $i => $row)
                <tr data-row data-name="{{ $row['name'] }}" data-city="{{ $row['address'] }}" data-vehicles="{{ $row['total_vehicles'] }}" data-active="{{ $row['available'] }}" data-status="{{ $row['status'] }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td class="px-3 py-3 font-medium text-neutral-900" data-label="Depot">
                        <div>
                            <p class="font-bold text-neutral-900">{{ $row['name'] }}</p>
                            <p class="text-xs text-neutral-500 font-medium">{{ $row['address'] }}</p>
                        </div>
                    </td>
                    <td class="px-3 py-3 font-mono text-xs tabular-nums text-neutral-600" data-label="Location">{{ $row['lat'] }}, {{ $row['lng'] }}</td>
                    <td class="px-3 py-3 text-right tabular-nums font-bold text-neutral-800" data-label="Vehicles">{{ $row['total_vehicles'] }}</td>
                    <td class="px-3 py-3 text-right tabular-nums font-bold text-emerald-700" data-label="Available">{{ $row['available'] }}</td>
                    <td class="px-3 py-3 text-right tabular-nums font-bold text-primary-700" data-label="Dispatched">{{ $row['dispatched'] }}</td>
                    <td class="px-3 py-3" data-label="Status"><x-status-badge :status="$row['status']" /></td>
                    <td class="px-3 py-3 text-right" data-label="">
                        <div class="flex items-center justify-end gap-1.5">
                            @if($canViewVehicles)
                                <x-btn size="sm" icon="truck" :href="route('fleet.vehicles')">Vehicles</x-btn>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-data-table>
    </x-card>

    @if($canCreateDepot)
        <x-modal name="depot-entry" title="Create Staging Depot" subtitle="Add a branch yard or vehicle staging location." icon="building-2" size="lg">
            <div x-data="depotFormAutoCoords(@js(\App\Livewire\Logistics\Depots::$locationCoordinates))">
                <form id="depot-entry-form" wire:submit="saveDepot" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form-field label="Depot name" name="name" wire:model.live.debounce.250ms="name" @input="autoDetect($event.target.value, true)" placeholder="e.g. Baliuag Satellite Yard" :error="$errors->first('name')" required />
                        
                        <div>
                            <x-form-field label="City / municipality" name="address" wire:model.live.debounce.250ms="address" @input="autoDetect($event.target.value, false)" @change="autoDetect($event.target.value, false)" list="luzon-cities-datalist" placeholder="Type or select city (e.g. Quezon City, Manila, Baliuag)" :error="$errors->first('address')" required />
                            <datalist id="luzon-cities-datalist">
                                @foreach(\App\Livewire\Logistics\Depots::$locationCoordinates as $city => $coords)
                                    <option value="{{ $city }}, {{ $coords['region'] }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <!-- Auto-coordinates indicator banner -->
                    <div class="rounded-[8px] border border-primary-200 bg-primary-50/70 p-3 text-xs text-primary-900">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 font-medium">
                                <x-icon name="map-pin" class="h-4 w-4 text-primary-600 shrink-0" />
                                <span>GPS Coordinates automatically populate from Depot name or City across NCR & Luzon.</span>
                            </span>
                            <span x-show="matchedCity" class="inline-flex items-center gap-1 rounded bg-primary-600 px-2 py-0.5 text-[11px] font-bold text-white shadow-xs">
                                📍 Detected: <span x-text="matchedCity"></span>
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form-field label="Latitude" type="number" step="any" name="latitude" wire:model="latitude" suffix="°N" tabular :error="$errors->first('latitude')" required />
                        <x-form-field label="Longitude" type="number" step="any" name="longitude" wire:model="longitude" suffix="°E" tabular :error="$errors->first('longitude')" required />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form-field label="Operational status" type="select" name="status" wire:model="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :error="$errors->first('status')" required />
                        
                        <div class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-2.5 text-xs text-neutral-600">
                            <span class="font-medium text-neutral-800">Quick Regional Presets:</span>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                <button type="button" @click="setPreset('Quezon City', 'Metro Manila', 14.6760410, 121.0437000)" class="rounded bg-white px-2 py-1 border border-neutral-300 hover:bg-neutral-100 font-medium text-[11px] transition-colors">Quezon City</button>
                                <button type="button" @click="setPreset('Manila', 'Metro Manila', 14.5995120, 120.9842190)" class="rounded bg-white px-2 py-1 border border-neutral-300 hover:bg-neutral-100 font-medium text-[11px] transition-colors">Manila</button>
                                <button type="button" @click="setPreset('Taguig', 'Metro Manila', 14.5176180, 121.0508650)" class="rounded bg-white px-2 py-1 border border-neutral-300 hover:bg-neutral-100 font-medium text-[11px] transition-colors">Taguig/BGC</button>
                                <button type="button" @click="setPreset('Baliuag', 'Bulacan', 14.9536000, 120.9015000)" class="rounded bg-white px-2 py-1 border border-neutral-300 hover:bg-neutral-100 font-medium text-[11px] transition-colors">Baliuag</button>
                                <button type="button" @click="setPreset('San Fernando', 'Pampanga', 15.0298000, 120.6896000)" class="rounded bg-white px-2 py-1 border border-neutral-300 hover:bg-neutral-100 font-medium text-[11px] transition-colors">Pampanga</button>
                                <button type="button" @click="setPreset('Antipolo', 'Rizal', 14.5842000, 121.1764000)" class="rounded bg-white px-2 py-1 border border-neutral-300 hover:bg-neutral-100 font-medium text-[11px] transition-colors">Antipolo</button>
                                <button type="button" @click="setPreset('Imus', 'Cavite', 14.4296000, 120.9367000)" class="rounded bg-white px-2 py-1 border border-neutral-300 hover:bg-neutral-100 font-medium text-[11px] transition-colors">Cavite</button>
                                <button type="button" @click="setPreset('Santa Rosa', 'Laguna', 14.3122000, 121.1114000)" class="rounded bg-white px-2 py-1 border border-neutral-300 hover:bg-neutral-100 font-medium text-[11px] transition-colors">Laguna</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="check" wire:click="saveDepot" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveDepot">Save Depot</span>
                    <span wire:loading wire:target="saveDepot">Saving...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    <x-modal name="export-depots" title="Export Depot Directory" icon="download">
        <x-form-field label="Format" type="select" name="format" :options="['CSV' => 'CSV Spreadsheet', 'PDF' => 'PDF Document']" />
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" @click="$dispatch('close-modal')">Download</x-btn>
        </x-slot:footer>
    </x-modal>
</div>

@script
<script>
    Alpine.data('depotFormAutoCoords', (locations) => ({
        locations: locations || {},
        matchedCity: '',

        autoDetect(input, isName = false) {
            if (!input) {
                this.matchedCity = '';
                return;
            }
            const clean = input.toLowerCase().trim();
            const primary = clean.split(',')[0].trim();
            const sortedEntries = Object.entries(this.locations).sort((a, b) => b[0].length - a[0].length);

            // 1. Check primary text segment first (e.g. "Taguig" in "Taguig, Metro Manila")
            for (const [city, coords] of sortedEntries) {
                const cLower = city.toLowerCase();
                if (cLower === 'bulakan' && !primary.includes('bulakan')) continue;
                if (cLower === 'manila' && primary.includes('metro manila') && !primary.match(/\b(city of manila|manila)\b/)) continue;

                if (primary.includes(cLower)) {
                    this.matchedCity = city + ' (' + (coords.region || 'Luzon') + ')';
                    this.$wire.set('latitude', coords.lat.toFixed(7));
                    this.$wire.set('longitude', coords.lng.toFixed(7));
                    if (isName && (!this.$wire.address || this.$wire.address.trim() === '')) {
                        this.$wire.set('address', city + ', ' + (coords.region || 'Luzon'));
                    }
                    return;
                }
            }

            // 2. Fallback to full string
            for (const [city, coords] of sortedEntries) {
                const cLower = city.toLowerCase();
                if (cLower === 'bulakan' && !clean.includes('bulakan')) continue;
                if (cLower === 'manila' && !clean.match(/\b(city of manila|manila)\b/)) continue;

                if (clean.includes(cLower)) {
                    this.matchedCity = city + ' (' + (coords.region || 'Luzon') + ')';
                    this.$wire.set('latitude', coords.lat.toFixed(7));
                    this.$wire.set('longitude', coords.lng.toFixed(7));
                    if (isName && (!this.$wire.address || this.$wire.address.trim() === '')) {
                        this.$wire.set('address', city + ', ' + (coords.region || 'Luzon'));
                    }
                    return;
                }
            }
        },

        setPreset(city, region, lat, lng) {
            this.matchedCity = city + ' (' + region + ')';
            this.$wire.set('address', city + ', ' + region);
            this.$wire.set('latitude', parseFloat(lat).toFixed(7));
            this.$wire.set('longitude', parseFloat(lng).toFixed(7));
        }
    }));
    Alpine.data('depotsMapComponent', (depots) => ({
        depots: depots || [],
        map: null,
        markers: {},

        init() {
            setTimeout(() => {
                this.initLeaflet();
            }, 250);

            window.addEventListener('depot-created', (e) => {
                if (e.detail && e.detail.depot) {
                    this.depots.push(e.detail.depot);
                    this.initLeaflet();
                }
            });
        },

        initLeaflet() {
            const mapEl = document.getElementById('multi-depot-overview-map');
            if (!mapEl) return;

            if (this.map) {
                this.map.remove();
                this.map = null;
            }

            if (typeof L === 'undefined') return;

            // Center on Bulacan Region
            this.map = L.map(mapEl, {
                zoomControl: true,
                scrollWheelZoom: true
            }).setView([14.860, 120.800], 11);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);

            const bounds = L.latLngBounds();

            this.depots.forEach(depot => {
                const lat = parseFloat(depot.lat) || 14.8527390;
                const lng = parseFloat(depot.lng) || 120.8160380;
                bounds.extend([lat, lng]);

                // Circle marker per depot
                const color = depot.name.includes('Main') ? '#059669' : (depot.name.includes('Paombong') ? '#2563eb' : '#4f46e5');

                const marker = L.circleMarker([lat, lng], {
                    radius: 14,
                    fillColor: color,
                    color: '#ffffff',
                    weight: 3,
                    opacity: 1,
                    fillOpacity: 0.95
                }).addTo(this.map);

                marker.bindTooltip(`
                    <div style="font-family:sans-serif;padding:3px 6px;">
                        <strong style="color:${color};font-size:12px;display:block;">${depot.name}</strong>
                        <span style="font-weight:bold;font-size:12px;color:#111827;">${depot.total_vehicles} Staged Fleet (${depot.available} Available)</span>
                    </div>
                `, { permanent: true, direction: 'top', offset: [0, -14] }).openTooltip();

                // Detailed popup content
                let typePills = '';
                if (depot.by_type) {
                    for (const [type, count] of Object.entries(depot.by_type)) {
                        typePills += `<span style="background:#e5e7eb;padding:2px 6px;border-radius:9999px;font-size:10px;font-weight:bold;margin-right:3px;">${type}: ${count}</span>`;
                    }
                }

                marker.bindPopup(`
                    <div style="font-family:sans-serif;padding:4px;min-width:200px;">
                        <span style="background:#d1fae5;color:#065f46;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:bold;text-transform:uppercase;">${depot.status}</span>
                        <h4 style="margin:6px 0 2px;font-weight:bold;font-size:14px;color:#111827;">${depot.name}</h4>
                        <p style="margin:0 0 8px;font-size:11px;color:#4b5563;">${depot.address}</p>
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px;background:#f3f4f6;padding:6px;border-radius:6px;text-align:center;font-size:11px;margin-bottom:8px;">
                            <div><span style="color:#6b7280;display:block;font-size:10px;">Available</span><strong style="color:#059669;font-size:13px;">${depot.available}</strong></div>
                            <div><span style="color:#6b7280;display:block;font-size:10px;">Dispatched</span><strong style="color:#2563eb;font-size:13px;">${depot.dispatched}</strong></div>
                            <div><span style="color:#6b7280;display:block;font-size:10px;">Repair</span><strong style="color:#e11d48;font-size:13px;">${depot.maintenance}</strong></div>
                        </div>

                        <div style="margin-top:6px;">${typePills}</div>
                    </div>
                `);

                this.markers[depot.id] = marker;
            });

            if (this.depots.length > 0) {
                this.map.fitBounds(bounds, { padding: [60, 60] });
            }

            setTimeout(() => {
                if (this.map) this.map.invalidateSize();
            }, 300);
        },

        focusDepot(id) {
            const depot = this.depots.find(d => d.id === id);
            if (!depot || !this.map) return;

            const lat = parseFloat(depot.lat) || 14.8527390;
            const lng = parseFloat(depot.lng) || 120.8160380;

            this.map.setView([lat, lng], 14, { animate: true });

            if (this.markers[id]) {
                this.markers[id].openPopup();
            }

            // Scroll map smooth into view
            const mapContainer = document.getElementById('multi-depot-overview-map');
            if (mapContainer) {
                mapContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }));
</script>
@endscript
