@php
    use App\Support\Format;
@endphp

<div>
    <x-page-header
        title="Fleet & Transportation Dashboard"
        subtitle="Field mobility, dispatch readiness, fuel control, and center logistics costs for {{ Format::date(now()) }}">
        <x-slot:actions>
            <x-btn icon="printer" @click="window.print()" aria-label="Print transportation dashboard">Print</x-btn>
            <x-btn variant="primary" icon="clipboard-check" :href="route('fleet.reservations')">New Reservation</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Fleet operations indicators"
             class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-stat-card label="Available Fleet" :value="$availableFleet" :delta="-1.8" good-direction="up" delta-caption="vs yesterday" icon="truck" />
        <x-stat-card label="Active Dispatches" :value="number_format($activeDispatches)" :delta="2.0" good-direction="up" delta-caption="today" icon="navigation" />
        <x-stat-card label="Today's Fuel Spend" :value="Format::peso($fuelSpend)" :delta="6.4" good-direction="down" delta-caption="vs plan" icon="fuel" />
        <x-stat-card label="Fleet Utilization" :value="number_format($utilization, 1).'%'" :delta="4.2" good-direction="up" delta-caption="this week" icon="gauge" />
        <x-stat-card label="ML Cost Variance" :value="'+'.number_format($variance, 1).'%'" :delta="$variance" good-direction="down" delta-caption="actual vs predicted" icon="cpu" />
    </section>

    <section class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card class="xl:col-span-2" flush>
            <x-slot:title>Dispatch Queue</x-slot:title>
            <x-slot:subtitle>Approved and pending MFI field movements tied to center operations</x-slot:subtitle>
            <x-slot:actions>
                <x-btn size="sm" icon="download" :href="route('intelligence.reports.export', 'operations')">CSV</x-btn>
                <x-btn size="sm" icon="navigation" :href="route('fleet.dispatch')">Open board</x-btn>
            </x-slot:actions>

            <x-data-table sort-key="eta" sort-dir="asc" caption="Approved and pending transportation reservations">
                <x-slot:head>
                    <x-th sort="code">Reservation</x-th>
                    <x-th sort="purpose">Purpose</x-th>
                    <x-th sort="center">Center</x-th>
                    <x-th sort="route">Route</x-th>
                    <x-th sort="eta">Departure</x-th>
                    <x-th sort="cost" align="right">Predicted Cost</x-th>
                    <x-th sort="status">Status</x-th>
                </x-slot:head>

                @forelse($dispatchQueue as $i => $row)
                    <tr data-row
                        data-code="{{ $row->reservation_number }}"
                        data-purpose="{{ $row->purpose }}"
                        data-center="{{ $row->route?->center_code }}"
                        data-route="{{ $row->route?->name }}"
                        data-eta="{{ $row->scheduled_start_at }}"
                        data-cost="{{ $row->predicted_cost }}"
                        data-status="{{ $row->status }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                        <td data-label="Reservation" class="px-3 py-2.5 font-medium tabular-nums text-neutral-800">{{ $row->reservation_number }}</td>
                        <td data-label="Purpose" class="px-3 py-2.5 text-neutral-700">{{ $row->purpose }}</td>
                        <td data-label="Center" class="px-3 py-2.5 text-neutral-600">{{ $row->route?->center_code ?? '-' }}</td>
                        <td data-label="Route" class="px-3 py-2.5 text-neutral-600">{{ $row->route?->name ?? '-' }}</td>
                        <td data-label="Departure" class="px-3 py-2.5 tabular-nums text-neutral-700">{{ Format::dateTime($row->scheduled_start_at) }}</td>
                        <td data-label="Predicted Cost" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row->predicted_cost) }}</td>
                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="str($row->status)->title()" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-sm text-neutral-500">No reservations in the queue.</td>
                    </tr>
                @endforelse
            </x-data-table>
        </x-card>

        <x-card title="Fleet Readiness" subtitle="Vehicles grouped by dispatch class">
            <div class="space-y-4">
                @forelse($fleetReadiness as $row)
                    @php $pct = $row['total'] > 0 ? round(($row['available'] / $row['total']) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-neutral-800">{{ $row['label'] }}</p>
                                <p class="text-xs text-neutral-500">{{ $row['note'] }}</p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-neutral-900">{{ $row['available'] }}/{{ $row['total'] }}</p>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-neutral-100">
                            <div class="h-2 rounded-full bg-primary-600" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-500">No vehicles registered yet.</p>
                @endforelse
            </div>
        </x-card>
    </section>

    <section class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-card title="Control Alerts" subtitle="Items that affect dispatch eligibility or cost control">
            <ol class="space-y-4">
                @forelse($alerts as $alert)
                    <li class="flex gap-3">
                        <span @class([
                            'grid h-9 w-9 shrink-0 place-items-center rounded-[8px]',
                            'bg-[#FEECEA] text-danger' => $alert['tone'] === 'danger',
                            'bg-[#FEF0D6] text-warning' => $alert['tone'] === 'warning',
                        ])>
                            <x-icon :name="$alert['icon']" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-neutral-800">{{ $alert['title'] }}</p>
                            <p class="text-[13px] text-neutral-500">{{ $alert['meta'] }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-neutral-500">No active control alerts.</li>
                @endforelse
            </ol>
        </x-card>

        <x-card title="Cost Allocation Snapshot" subtitle="Transportation costs mapped to MFI center service areas">
            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <x-kpi label="Cost / km" :value="Format::peso($costSnapshot['costPerKm'])" />
                <x-kpi label="Center cost" :value="Format::peso($costSnapshot['total'])" />
                <x-kpi label="Flagged vouchers" :value="number_format($costSnapshot['flaggedVouchers'])" />
            </dl>
            <div class="mt-4 rounded-[8px] border border-primary-200 bg-primary-50 p-3">
                <p class="text-[13px] font-medium text-primary-900">Highest logistics load</p>
                <p class="mt-1 text-sm text-primary-800">{{ $costSnapshot['highestRoute'] }} currently carries the highest posted transport cost.</p>
            </div>
        </x-card>
    </section>
</div>
