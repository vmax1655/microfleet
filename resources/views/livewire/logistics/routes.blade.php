@php
    $routes = [
        ['code' => 'RTE-SM-04', 'name' => 'Malolos - San Miguel Center 04',          'center' => 'CTR-04', 'distance' => '31.4 km', 'duration' => '70 min', 'profile' => 'Mixed', 'status' => 'Active'],
        ['code' => 'RTE-PB-02', 'name' => 'Paombong Satellite - Paombong Center 02',  'center' => 'CTR-02', 'distance' => '10.8 km', 'duration' => '28 min', 'profile' => 'Urban', 'status' => 'Active'],
        ['code' => 'RTE-CP-11', 'name' => 'Calumpit Field - Calumpit Center 11',      'center' => 'CTR-11', 'distance' => '18.6 km', 'duration' => '44 min', 'profile' => 'Mixed', 'status' => 'Active'],
    ];
@endphp

<div>
    <x-breadcrumb />

    <x-page-header title="Center Routes" subtitle="Barangay center route definitions, distances, and schedules.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-routes')">Export</x-btn>
            <x-btn variant="primary" icon="compass" @click="$dispatch('open-modal', 'route-entry')">New Route</x-btn>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mt-6" flush>
        <x-slot:title>Route Catalog</x-slot:title>
        <x-slot:subtitle>Routes feed reservation estimates, dispatch planning, and cost-per-center reports.</x-slot:subtitle>
        <x-data-table sort-key="code" caption="Center route catalog">
            <x-slot:head>
                <x-th sort="code">Code</x-th>
                <x-th sort="name">Route</x-th>
                <x-th sort="center">Center</x-th>
                <x-th sort="distance">Distance</x-th>
                <x-th sort="duration">Duration</x-th>
                <x-th sort="profile">Road Profile</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>
            @foreach($routes as $i => $row)
                <tr data-row data-code="{{ $row['code'] }}" data-name="{{ $row['name'] }}" data-center="{{ $row['center'] }}" data-distance="{{ $row['distance'] }}" data-duration="{{ $row['duration'] }}" data-profile="{{ $row['profile'] }}" data-status="{{ $row['status'] }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td class="px-3 py-2.5 font-medium tabular-nums text-neutral-800" data-label="Code">{{ $row['code'] }}</td>
                    <td class="px-3 py-2.5 text-neutral-700" data-label="Route">{{ $row['name'] }}</td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Center">{{ $row['center'] }}</td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Distance">{{ $row['distance'] }}</td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Duration">{{ $row['duration'] }}</td>
                    <td class="px-3 py-2.5 text-neutral-600" data-label="Road Profile">{{ $row['profile'] }}</td>
                    <td class="px-3 py-2.5" data-label="Status"><x-status-badge :status="$row['status']" /></td>
                    <td class="px-3 py-2.5 text-right" data-label=""><x-btn size="sm" icon="clipboard-check" :href="route('fleet.reservations')">Reserve</x-btn></td>
                </tr>
            @endforeach
        </x-data-table>
    </x-card>

    <x-modal name="route-entry" title="Create Center Route" subtitle="Add a depot-to-center route for reservation planning." icon="compass">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form-field label="Route code" name="route_code" value="RTE-NEW" required />
            <x-form-field label="Center code" name="center_code" value="CTR-12" required />
            <x-form-field label="Route name" name="name" value="Malolos - New Center" required />
            <x-form-field label="Road profile" type="select" name="profile" :options="['Urban', 'Mixed', 'Rural']" />
            <x-form-field label="Distance" type="number" name="distance" suffix="km" value="12.50" tabular required />
            <x-form-field label="Duration" type="number" name="duration" suffix="min" value="35" tabular required />
        </div>
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check" @click="$dispatch('close-modal')">Save Route</x-btn>
        </x-slot:footer>
    </x-modal>

    <x-modal name="export-routes" title="Export Route Catalog" icon="download">
        <x-form-field label="Format" type="select" name="format" :options="['CSV', 'PDF']" />
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" @click="$dispatch('close-modal')">Download</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
