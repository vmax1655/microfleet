@php
    use App\Support\Format;

    $avgPrice = (float) $litersLogged > 0 ? (float) $fuelSpendTotal / (float) $litersLogged : 0;
@endphp

<div>
    <x-breadcrumb />

    {{-- Dismissible Alert Banner --}}
    @if($bannerMessage)
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 7000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="mb-4 flex items-center justify-between rounded-[8px] border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm"
             role="alert">
            <div class="flex items-center gap-2">
                <x-icon name="check-circle" class="h-5 w-5 shrink-0 text-emerald-600" />
                <span class="font-medium">{{ $bannerMessage }}</span>
            </div>
            <button @click="show = false" class="text-emerald-600 hover:text-emerald-900">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif

    <x-page-header title="Fuel Transactions" subtitle="Fuel-up records, liter consumption, anomaly review, and cost per kilometer.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-fuel')">Export</x-btn>
            @if($canLogFuel)
                <x-btn variant="primary" icon="fuel"
                    wire:click="openLogFuelModal"
                    @click="$dispatch('open-modal', 'fuel-entry')">Log Fuel</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
        <x-stat-card label="Fuel Spend" :value="Format::peso($fuelSpendTotal)" icon="fuel" />
        <x-stat-card label="Liters Logged" :value="number_format((float) $litersLogged, 2).' L'" icon="gauge" />
        <x-stat-card label="Avg Price / L" :value="Format::peso($avgPrice)" icon="calculator" />
        <x-stat-card label="For Review" :value="number_format($reviewCount)" icon="alert-triangle" />
    </section>

    <x-card class="mt-5" flush>
        <x-slot:title>Fuel Ledger</x-slot:title>
        <x-slot:subtitle>Fuel records are linked to vehicles, drivers, trips, and transport cost analysis.</x-slot:subtitle>

        <x-data-table sort-key="receipt" caption="Fuel transactions">
            <x-slot:head>
                <x-th sort="receipt">Receipt</x-th>
                <x-th sort="vehicle">Vehicle</x-th>
                <x-th sort="driver">Driver</x-th>
                <x-th sort="trip">Trip</x-th>
                <x-th sort="station">Station</x-th>
                <x-th sort="liters" align="right">Liters</x-th>
                <x-th sort="total" align="right">Total</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>

            @forelse($fuelRows as $i => $row)
                <tr data-row data-receipt="{{ $row->receipt_number }}" data-vehicle="{{ $row->vehicle?->plate_number }}" data-driver="{{ $row->driver?->user?->name }}" data-trip="{{ $row->trip?->trip_number }}" data-station="{{ $row->station_name }}" data-liters="{{ $row->liters }}" data-total="{{ $row->total_cost }}" data-status="{{ $row->status }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td data-label="Receipt" class="px-3 py-2.5 font-medium tabular-nums text-neutral-800">
                        {{ $row->receipt_number ?? 'Unreceipted' }}
                        <span class="block text-[11px] text-neutral-400">{{ $row->fueled_at?->format('M d, Y') }}</span>
                    </td>
                    <td data-label="Vehicle" class="px-3 py-2.5 tabular-nums text-neutral-700 font-medium">
                        {{ $row->vehicle?->plate_number ?? '-' }}
                        <span class="block text-[11px] text-neutral-400">{{ ucfirst($row->fuel_type ?? 'Diesel') }} · {{ number_format((float) $row->odometer_km) }} km</span>
                    </td>
                    <td data-label="Driver" class="px-3 py-2.5 text-neutral-600">{{ $row->driver?->user?->name ?? '—' }}</td>
                    <td data-label="Trip" class="px-3 py-2.5 tabular-nums text-neutral-600">
                        @if($row->trip)
                            <span class="font-medium text-primary-700">{{ $row->trip->trip_number }}</span>
                        @else
                            <span class="text-neutral-400">—</span>
                        @endif
                    </td>
                    <td data-label="Station" class="px-3 py-2.5 text-neutral-600">{{ $row->station_name }}</td>
                    <td data-label="Liters" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">
                        {{ number_format((float) $row->liters, 2) }} L
                        <span class="block text-[11px] text-neutral-400">@ {{ Format::peso($row->unit_price) }}</span>
                    </td>
                    <td data-label="Total" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row->total_cost) }}</td>
                    <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="str($row->status)->replace('_', ' ')->title()" /></td>
                    <td data-label="" class="px-3 py-2.5 text-right">
                        @if($canViewCosts)
                            <x-btn size="sm" icon="calculator" :href="route('intelligence.costs')">Cost</x-btn>
                        @else
                            <span class="text-sm text-neutral-400">Posted</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-3 py-8 text-center text-sm text-neutral-500">No fuel transactions yet. Click <strong>Log Fuel</strong> to record the first transaction.</td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    {{-- ── Log Fuel Transaction Modal ── --}}
    @if($canLogFuel)
        <x-modal name="fuel-entry" title="Log Fuel Transaction" subtitle="Attach fuel purchase to a vehicle, driver, and trip." icon="fuel" size="lg">
            <form id="fuel-entry-form" wire:submit.prevent="saveFuel" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Vehicle" type="select" name="vehicle"
                        wire:model.live="vehicle"
                        :options="$vehicleOptions ?: ['' => '— No vehicles registered —']"
                        :error="$errors->first('vehicle')"
                        required />

                    <x-form-field label="Associated Trip (optional)" type="select" name="trip"
                        wire:model.live="trip"
                        :options="array_merge(['' => '— None (General fill-up) —'], $tripOptions)"
                        :error="$errors->first('trip')" />

                    <x-form-field label="Driver (optional)" type="select" name="driver"
                        wire:model="driver"
                        :options="array_merge(['' => '— Auto-detect from Trip/Vehicle —'], $driverOptions)"
                        :error="$errors->first('driver')" />

                    <x-form-field label="Station" name="station"
                        wire:model="station"
                        placeholder="e.g. Petron Malolos, Shell QC"
                        :error="$errors->first('station')"
                        required />

                    <x-form-field label="Receipt no." name="receipt"
                        wire:model="receipt"
                        :error="$errors->first('receipt')"
                        required />

                    <x-form-field label="Fuel date" type="date" name="fueled_at"
                        wire:model="fueled_at"
                        :error="$errors->first('fueled_at')"
                        required />

                    <x-form-field label="Liters" type="number" name="liters"
                        wire:model.live="liters"
                        step="0.01" min="0.01" tabular
                        :error="$errors->first('liters')"
                        required />

                    <x-form-field label="Price / liter" type="number" name="unit_price"
                        wire:model.live="unit_price"
                        step="0.01" min="0.01" prefix="PHP" tabular
                        :error="$errors->first('unit_price')"
                        required />

                    <x-form-field label="Odometer" type="number" name="odometer"
                        wire:model="odometer"
                        suffix="km" min="0" tabular
                        :error="$errors->first('odometer')"
                        required />

                    {{-- Live Total Cost Calculation Card --}}
                    <div class="flex items-center justify-between rounded-[8px] border border-primary-200 bg-primary-50/70 p-3.5">
                        <div>
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-primary-700">Calculated Total</span>
                            <span class="text-xs text-primary-600">
                                {{ number_format((float) ($liters ?: 0), 2) }} L × ₱{{ number_format((float) ($unit_price ?: 0), 2) }}
                            </span>
                        </div>
                        <span class="text-lg font-bold text-primary-800">
                            {{ Format::peso(round((float) ($liters ?: 0) * (float) ($unit_price ?: 0), 2)) }}
                        </span>
                    </div>
                </div>
            </form>

            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="check"
                    wire:click="saveFuel"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveFuel">Save Fuel</span>
                    <span wire:loading wire:target="saveFuel">Saving...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- ── Export Fuel Ledger Modal ── --}}
    <x-modal name="export-fuel" title="Export Fuel Ledger" subtitle="Download full fuel records as CSV for audit and reconciliation." icon="download">
        <p class="text-sm text-neutral-600">
            Click <strong>Download CSV</strong> to export all recorded fuel transactions including receipts, vehicle plates, driver details, odometer readings, and costs.
        </p>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" wire:click="exportCsv">Download CSV</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
