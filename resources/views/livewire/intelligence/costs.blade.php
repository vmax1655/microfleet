@php
    use App\Support\Format;
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

    <x-page-header title="Transport Cost Analysis" subtitle="Per-center and per-trip cost breakdowns linked to field operations.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-costs')">Export CSV</x-btn>
            <x-btn icon="file-text" @click="window.print()">Print / PDF</x-btn>
            @if($canRecalculateCosts)
                <x-btn variant="primary" icon="calculator"
                    wire:click="openRecalculateModal"
                    @click="$dispatch('open-modal', 'recalculate-cost')">Recalculate</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
        <x-stat-card label="Total Cost" :value="Format::peso($summary['total'])" icon="calculator" />
        <x-stat-card label="Avg Cost / km" :value="Format::peso($summary['costPerKm'])" icon="gauge" />
        <x-stat-card label="Highest Cost Center" :value="$summary['highestCenter']" icon="map-pin" />
        <x-stat-card label="Trips Rolled Up" :value="number_format($costs->count())" icon="check-circle" />
    </section>

    <x-card class="mt-5" flush>
        <x-slot:title>Trip Cost Rollups</x-slot:title>
        <x-slot:subtitle>Fuel, expenses, and maintenance allocation are consolidated per trip.</x-slot:subtitle>

        <x-slot:actions>
            @if($canRecalculateCosts)
                <x-btn size="sm" variant="secondary" icon="refresh-cw" wire:click="recalculateAll" wire:loading.attr="disabled" wire:target="recalculateAll">
                    <span wire:loading.remove wire:target="recalculateAll">Sync All</span>
                    <span wire:loading wire:target="recalculateAll">Syncing...</span>
                </x-btn>
            @endif
        </x-slot:actions>

        <x-data-table sort-key="trip" caption="Transport cost rollups">
            <x-slot:head>
                <x-th sort="trip">Trip</x-th>
                <x-th sort="center">Center</x-th>
                <x-th sort="fuel" align="right">Fuel</x-th>
                <x-th sort="expenses" align="right">Expenses</x-th>
                <x-th sort="maintenance" align="right">Maint. Alloc.</x-th>
                <x-th sort="total" align="right">Total</x-th>
                <x-th sort="cpk" align="right">Cost / km</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>

            @forelse($costs as $i => $row)
                <tr wire:key="cost-row-{{ $row->id }}"
                    data-row
                    data-trip="{{ $row->trip?->trip_number }}"
                    data-center="{{ $row->center_code }}"
                    data-fuel="{{ $row->fuel_cost }}"
                    data-expenses="{{ $row->expense_cost }}"
                    data-maintenance="{{ $row->maintenance_allocation }}"
                    data-total="{{ $row->total_cost }}"
                    data-cpk="{{ $row->cost_per_km }}"
                    data-status="{{ $row->status }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td data-label="Trip" class="px-3 py-2.5 font-medium tabular-nums text-neutral-800">
                        {{ $row->trip?->trip_number ?? '-' }}
                        @if($row->trip?->vehicle)
                            <span class="block text-[11px] text-neutral-400">
                                {{ $row->trip->vehicle->plate_number }} · {{ $row->trip->driver?->user?->name ?? 'No Driver' }}
                            </span>
                        @endif
                    </td>
                    <td data-label="Center" class="px-3 py-2.5 tabular-nums text-neutral-700 font-medium">
                        {{ $row->center_code ?? '-' }}
                        @if($row->trip?->distance_km)
                            <span class="block text-[11px] text-neutral-400">{{ number_format((float) $row->trip->distance_km, 1) }} km</span>
                        @endif
                    </td>
                    <td data-label="Fuel" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ Format::peso($row->fuel_cost) }}</td>
                    <td data-label="Expenses" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ Format::peso($row->expense_cost) }}</td>
                    <td data-label="Maint. Alloc." class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ Format::peso($row->maintenance_allocation) }}</td>
                    <td data-label="Total" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row->total_cost) }}</td>
                    <td data-label="Cost / km" class="px-3 py-2.5 text-right font-medium tabular-nums text-primary-700">{{ Format::peso($row->cost_per_km) }}</td>
                    <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="str($row->status)->title()" /></td>
                    <td data-label="" class="px-3 py-2.5 text-right">
                        @if($canRecalculateCosts && $row->trip_id)
                            <x-btn size="xs" variant="ghost" icon="refresh-cw"
                                wire:click="recalculateSingle({{ $row->trip_id }})"
                                wire:loading.attr="disabled"
                                wire:target="recalculateSingle({{ $row->trip_id }})"
                                title="Recalculate this trip">
                                <span wire:loading.remove wire:target="recalculateSingle({{ $row->trip_id }})">Sync</span>
                                <span wire:loading wire:target="recalculateSingle({{ $row->trip_id }})">...</span>
                            </x-btn>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-3 py-8 text-center text-sm text-neutral-500">No transport cost rollups yet. Click <strong>Recalculate</strong> to generate trip cost rollups.</td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    {{-- ── Recalculate Modal ── --}}
    @if($canRecalculateCosts)
        <x-modal name="recalculate-cost" title="Recalculate Transport Cost" subtitle="Rebuild cost rollup from fuel, expenses, maintenance allocation, and distance." icon="calculator" size="md">
            <div class="space-y-4">
                <x-form-field label="Select Trip to Recalculate" type="select" name="selected_trip"
                    wire:model.live="selected_trip"
                    :value="$selected_trip"
                    :options="array_merge(['all' => '— All Completed & Active Trips —'], $tripOptions)"
                    required />

                <div class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-3 text-xs text-neutral-600 space-y-1">
                    <p class="font-semibold text-neutral-800">What happens during recalculation?</p>
                    <ul class="list-disc pl-4 space-y-0.5">
                        <li>Aggregates all fuel transactions attached to the trip.</li>
                        <li>Sums all approved and recorded trip expenses (tolls, parking, etc.).</li>
                        <li>Allocates maintenance costs per kilometer based on vehicle type.</li>
                        <li>Updates the total trip cost and cost-per-kilometer metrics.</li>
                    </ul>
                </div>
            </div>

            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="refresh-cw"
                    wire:click="recalculate"
                    wire:loading.attr="disabled"
                    wire:target="recalculate">
                    <span wire:loading.remove wire:target="recalculate">Run Recalculation</span>
                    <span wire:loading wire:target="recalculate">Calculating...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- ── Export Modal ── --}}
    <x-modal name="export-costs" title="Export Transport Cost Analysis" subtitle="Download full cost rollup records as CSV for financial audit and reconciliation." icon="download">
        <p class="text-sm text-neutral-600 mb-4">Export transport cost breakdowns including fuel, approved expenses, maintenance allocations, and cost-per-kilometer metrics per trip.</p>
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal', 'export-costs')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" wire:click="exportCsv" @click="$dispatch('close-modal', 'export-costs')">Download CSV</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
