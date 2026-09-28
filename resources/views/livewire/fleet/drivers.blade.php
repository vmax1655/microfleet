@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Driver Management"
        subtitle="Driver qualifications, LTO license categories, authorized vehicle classes, and readiness.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-drivers')">Export</x-btn>
            @if($canCreateDriver)
                <x-btn variant="primary" icon="user-plus" @click="$dispatch('open-modal', 'driver-entry')">New Driver</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
        <x-stat-card label="Available Drivers" :value="number_format($drivers->where('availability_status', 'available')->count())" icon="user-check" />
        <x-stat-card label="Assigned Today" :value="number_format($drivers->whereIn('availability_status', ['assigned', 'in_transit'])->count())" icon="navigation" />
        <x-stat-card label="License Alerts" :value="number_format($drivers->filter(fn ($driver) => $driver->license_expires_at->lte(now()->addDays(30)))->count())" icon="shield" />
        <x-stat-card label="Avg Safety Score" :value="number_format((float) $drivers->avg('safety_score'), 1).'%'" icon="award" />
    </section>

    <x-card class="mt-5" flush>
        <x-slot:title>Driver Qualifications & LTO License Registry</x-slot:title>
        <x-slot:subtitle>Admins can verify LTO DL Categories (Code A, A1, B, B1, B2) and authorized vehicle classes before dispatching.</x-slot:subtitle>

        <x-data-table sort-key="name" caption="Driver readiness board">
            <x-slot:head>
                <x-th sort="name">Driver</x-th>
                <x-th sort="license">DL Restrictions & LTO Category</x-th>
                <x-th>Authorized Vehicle Classes</x-th>
                <x-th sort="expires">License Expires</x-th>
                <x-th sort="assigned">Assigned Vehicle</x-th>
                <x-th sort="score">Safety Score</x-th>
                <x-th sort="status">Status</x-th>
            </x-slot:head>

            @forelse($drivers as $i => $row)
                @php
                    $vehicle = $row->dispatches->sortByDesc('assigned_at')->first()?->vehicle?->plate_number ?? '-';
                    $status = str($row->availability_status)->replace('_', ' ')->title();
                    $authorized = $row->authorizedVehicleTypes();
                @endphp
                <tr data-row data-name="{{ $row->user?->name }}" data-license="{{ $row->license_restrictions }}" data-expires="{{ $row->license_expires_at }}" data-assigned="{{ $vehicle }}" data-score="{{ $row->safety_score }}" data-status="{{ $status }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td data-label="Driver" class="px-3 py-3 font-medium text-neutral-900">
                        <div>
                            <p class="font-bold text-neutral-800">{{ $row->user?->name ?? '-' }}</p>
                            <p class="text-[11px] font-mono text-neutral-500">{{ $row->employee_number }} · {{ $row->license_number }}</p>
                        </div>
                    </td>
                    <td data-label="DL Restrictions" class="px-3 py-3 text-neutral-700">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1 rounded-md bg-neutral-100 px-2 py-0.5 text-xs font-bold text-neutral-800 border border-neutral-300">
                                {{ $row->license_restrictions }}
                            </span>
                            <p class="text-[11px] text-neutral-500 font-medium">{{ $row->licenseCategoryLabel() }}</p>
                        </div>
                    </td>
                    <td data-label="Authorized Vehicles" class="px-3 py-3">
                        <div class="flex flex-wrap gap-1">
                            @foreach($authorized as $vType)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-300 shadow-2xs">
                                    {{ $vType }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td data-label="License Expires" class="px-3 py-3 tabular-nums text-neutral-600 text-xs">{{ Format::date($row->license_expires_at) }}</td>
                    <td data-label="Assigned Vehicle" class="px-3 py-3 tabular-nums font-semibold text-neutral-800 text-xs">{{ $vehicle }}</td>
                    <td data-label="Safety Score" class="px-3 py-3 font-medium tabular-nums text-neutral-800 text-xs">{{ number_format((float) $row->safety_score, 1) }}%</td>
                    <td data-label="Status" class="px-3 py-3"><x-status-badge :status="$status" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-neutral-500">No drivers registered yet.</td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    @if($canCreateDriver)
        <x-modal name="driver-entry" title="Register Driver Profile" subtitle="Verify LTO DL Category qualifications before registering." icon="user-plus">
            <form id="driver-entry-form" wire:submit="saveDriver">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Driver full name" name="name" wire:model="name" required />
                    <x-form-field label="Employee no." name="employee" wire:model="employee" required />
                    <x-form-field label="License no." name="license" wire:model="license" required />
                    <x-form-field label="LTO DL Category / Restrictions" type="select" name="restrictions" wire:model="restrictions" :options="$restrictionOptions" required />
                    <x-form-field label="License expiry date" type="date" name="expires" wire:model="expires" required />
                    <x-form-field label="Availability status" type="select" name="status" wire:model="status" :options="['available' => 'Available', 'off_duty' => 'Off Duty', 'suspended' => 'Suspended']" required />
                </div>
            </form>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="check" type="submit" form="driver-entry-form">Save Driver Profile</x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    <x-modal name="export-drivers" title="Export Driver Readiness" icon="download">
        <x-form-field label="Format" type="select" name="format" :options="['CSV', 'PDF']" />
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" @click="$dispatch('close-modal')">Download</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
