@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    {{-- Banner feedback --}}
    @if($bannerMessage)
        <div class="mb-4 flex items-center gap-3 rounded-[10px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-xs"
             x-data x-init="setTimeout(() => $el.remove(), 6000)">
            <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-emerald-600" />
            {{ $bannerMessage }}
        </div>
    @endif

    <x-page-header
        title="Vehicle Reservations"
        subtitle="Book branch vehicles for center collections, loan releases, KYC visits, audits, and cash transfers.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-reservations')">Export</x-btn>
            @if($canCreateReservation)
                <x-btn variant="primary" icon="clipboard-check" @click="$dispatch('open-modal', 'reservation-entry')">New Reservation</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Stat Cards --}}
    <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
        <x-stat-card label="Pending Approval" :value="number_format($reservations->where('status', 'pending')->count())" icon="clock" hint="Fleet Manager queue" />
        <x-stat-card label="Approved Today"   :value="number_format($reservations->where('status', 'approved')->count())" icon="check-circle" hint="Ready for assignment" />
        <x-stat-card label="Assigned / Active" :value="number_format($reservations->where('status', 'assigned')->count())" icon="navigation" hint="Vehicle & driver assigned" />
        <x-stat-card label="Predicted Trip Cost" :value="Format::peso($reservations->whereIn('status', ['pending','approved','assigned'])->sum('predicted_cost'))" icon="calculator" hint="For active requests" />
    </section>

    {{-- Reservation Worklist --}}
    <x-card class="mt-5" flush>
        <x-slot:title>Reservation Worklist</x-slot:title>
        <x-slot:subtitle>Each request is tied to a purpose, center code, route window, and ML cost estimate.</x-slot:subtitle>

        <x-data-table sort-key="code" sort-dir="asc" caption="Fleet reservation worklist">
            <x-slot:head>
                <x-th sort="code">Reservation</x-th>
                <x-th sort="requester">Requester</x-th>
                <x-th sort="purpose">Purpose</x-th>
                <x-th sort="center">Center / Route</x-th>
                <x-th sort="vehicle">Class</x-th>
                <x-th sort="window">Time Window</x-th>
                <x-th sort="assignment">Assigned To</x-th>
                <x-th sort="predicted" align="right">Est. Cost</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>

            @forelse($reservations as $i => $row)
                @php
                    $status    = str($row->status)->replace('_', ' ')->title();
                    $dispatch  = $row->dispatch;
                @endphp
                <tr data-row
                    data-code="{{ $row->reservation_number }}"
                    data-requester="{{ $row->requester?->name }}"
                    data-purpose="{{ $row->purpose }}"
                    data-center="{{ $row->route?->center_code }}"
                    data-vehicle="{{ $row->vehicleType?->name }}"
                    data-window="{{ $row->scheduled_start_at }}"
                    data-assignment="{{ $dispatch?->vehicle?->plate_number }}"
                    data-predicted="{{ $row->predicted_cost }}"
                    data-status="{{ $status }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td data-label="Reservation" class="px-3 py-2.5 font-medium tabular-nums text-neutral-800">{{ $row->reservation_number }}</td>
                    <td data-label="Requester"   class="px-3 py-2.5 text-neutral-700">{{ $row->requester?->name ?? '—' }}</td>
                    <td data-label="Purpose"     class="px-3 py-2.5 text-neutral-700">{{ $row->purpose }}</td>
                    <td data-label="Center / Route" class="px-3 py-2.5 text-neutral-600">
                        {{ $row->route?->center_code ?? '—' }}
                        @if($row->route?->destination_name)
                            <span class="block text-[11px] text-neutral-400">{{ $row->route->destination_name }}</span>
                        @endif
                    </td>
                    <td data-label="Class"       class="px-3 py-2.5 text-neutral-600">{{ $row->vehicleType?->name ?? '—' }}</td>
                    <td data-label="Time Window" class="px-3 py-2.5 tabular-nums text-neutral-600">
                        {{ $row->scheduled_start_at->format('H:i') }}–{{ $row->scheduled_end_at->format('H:i') }}
                        <span class="block text-[11px] text-neutral-400">{{ $row->scheduled_start_at->format('M d') }}</span>
                    </td>
                    <td data-label="Assigned To" class="px-3 py-2.5 text-neutral-600">
                        @if($dispatch)
                            <span class="font-medium text-primary-700">{{ $dispatch->vehicle?->plate_number ?? '—' }}</span>
                            <span class="block text-[11px] text-neutral-400">{{ $dispatch->driver?->user?->name ?? '—' }}</span>
                        @elseif($row->preferredDriver)
                            <span class="text-neutral-400 text-[10px] uppercase font-semibold block">Requested:</span>
                            <span class="font-medium text-neutral-700">{{ $row->preferredDriver->user?->name }}</span>
                        @else
                            <span class="text-neutral-300">—</span>
                        @endif
                    </td>
                    <td data-label="Est. Cost"   class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row->predicted_cost) }}</td>
                    <td data-label="Status"      class="px-3 py-2.5"><x-status-badge :status="$status" /></td>
                    <td data-label=""            class="px-3 py-2.5 text-right">
                        @if($canReviewReservation && in_array($row->status, ['pending', 'approved']))
                            <x-btn size="sm" icon="eye"
                                wire:click="openReview('{{ $row->reservation_number }}')">Review</x-btn>
                        @else
                            <span class="text-xs text-neutral-400">{{ ucfirst($row->status) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-3 py-10 text-center text-sm text-neutral-500">
                        No reservations yet. Click <strong>New Reservation</strong> to create one.
                    </td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    {{-- ── New Reservation Modal ── --}}
    @if($canCreateReservation)
        <x-modal name="reservation-entry" title="Create Vehicle Reservation" subtitle="Request transport for a field operation." icon="clipboard-check" size="lg">
            <form id="reservation-entry-form" wire:submit.prevent="saveReservation" class="space-y-4">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Purpose" type="select" name="purpose"
                        wire:model="purpose"
                        :options="['Center Collection' => 'Center Collection', 'Loan Release' => 'Loan Release', 'KYC Verification' => 'KYC Verification', 'Cash Transfer' => 'Cash Transfer', 'Branch Audit' => 'Branch Audit']"
                        :error="$errors->first('purpose')"
                        required />
                    <div class="hidden sm:block"></div>
                </div>

                {{-- Point A / Point B --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-[8px] border-2 border-emerald-300 bg-emerald-50/60 p-3">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-black text-white shadow">A</span>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800">Point A · Origin Depot</span>
                        </div>
                        <x-form-field label="Select depot" type="select" name="origin_depot"
                            wire:model.live="origin_depot"
                            :options="$depotOptions"
                            :error="$errors->first('origin_depot')"
                            required />
                    </div>

                    <div class="rounded-[8px] border-2 border-rose-300 bg-rose-50/60 p-3">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-600 text-[10px] font-black text-white shadow">B</span>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-800">Point B · Destination Center or Depot</span>
                        </div>
                        <x-form-field label="Select destination" type="select" name="route"
                            wire:model="route"
                            :options="$destinationOptions"
                            :error="$errors->first('route')"
                            required />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Vehicle class" type="select" name="vehicle_type"
                        wire:model="vehicle_type"
                        :options="$vehicleTypeOptions"
                        :error="$errors->first('vehicle_type')"
                        required />

                    <x-form-field label="Preferred Driver" type="select" name="preferred_driver"
                        wire:model="preferred_driver"
                        :options="array_merge(['' => '— Optional: Auto-assign upon approval —'], $driverFormOptions)"
                        :error="$errors->first('preferred_driver')" />

                    <x-form-field label="Passenger count" type="number" name="passengers"
                        wire:model="passengers" min="1" tabular
                        :error="$errors->first('passengers')"
                        required />

                    <x-form-field label="Load level" type="select" name="load_level"
                        wire:model="load_level"
                        :options="['light' => 'Light', 'medium' => 'Medium', 'heavy' => 'Heavy']"
                        :error="$errors->first('load_level')"
                        required />

                    <x-form-field label="Start date & time" type="datetime-local" name="start"
                        wire:model="start"
                        :error="$errors->first('start')"
                        required />

                    <x-form-field label="End date & time" type="datetime-local" name="end"
                        wire:model="end"
                        :error="$errors->first('end')"
                        required />
                </div>

                <x-form-field label="Notes / Special instructions" type="textarea" name="notes"
                    wire:model="notes" rows="2"
                    :error="$errors->first('notes')" />
            </form>

            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                @if($canViewPrediction)
                    <x-btn icon="cpu" :href="route('intelligence.ml')">Predict Cost</x-btn>
                @endif
                <x-btn variant="primary" icon="check" wire:click="saveReservation" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveReservation">Submit Reservation</span>
                    <span wire:loading wire:target="saveReservation">Submitting...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- ── Review / Approve Modal ── --}}
    @if($canReviewReservation)
        <x-modal name="review-reservation" title="Review Reservation" subtitle="Approve or reject, and optionally assign a vehicle and driver." icon="eye" size="lg">
            <div class="space-y-4">
                {{-- Reservation picker --}}
                <x-form-field label="Reservation" type="select" name="review_reservation"
                    wire:model.live="review_reservation"
                    :options="$reviewOptions ?: ['' => '— No pending reservations —']"
                    :error="$errors->first('review_reservation')"
                    required />

                @if($review_reservation)
                    @php
                        $selectedRes = $reservations->firstWhere('reservation_number', $review_reservation);
                    @endphp
                    @if($selectedRes)
                        <div class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-3 text-xs space-y-1">
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                                <span class="text-neutral-500">Requester</span>
                                <span class="font-medium text-neutral-800">{{ $selectedRes->requester?->name ?? '—' }}</span>
                                <span class="text-neutral-500">Purpose</span>
                                <span class="font-medium text-neutral-800">{{ $selectedRes->purpose }}</span>
                                <span class="text-neutral-500">Route</span>
                                <span class="font-medium text-neutral-800">{{ $selectedRes->route?->route_code }} → {{ $selectedRes->route?->destination_name }}</span>
                                <span class="text-neutral-500">Vehicle Class</span>
                                <span class="font-medium text-neutral-800">{{ $selectedRes->vehicleType?->name ?? '—' }}</span>
                                <span class="text-neutral-500">Preferred Driver</span>
                                <span class="font-medium text-primary-700">{{ $selectedRes->preferredDriver?->user?->name ?? '— (None specified)' }}</span>
                                <span class="text-neutral-500">Schedule</span>
                                <span class="font-medium text-neutral-800">{{ $selectedRes->scheduled_start_at->format('M d, Y H:i') }} – {{ $selectedRes->scheduled_end_at->format('H:i') }}</span>
                                <span class="text-neutral-500">Predicted Cost</span>
                                <span class="font-semibold text-primary-700">{{ Format::peso($selectedRes->predicted_cost) }}</span>
                            </div>
                            @if($selectedRes->notes)
                                <div class="mt-2 rounded bg-neutral-100 p-2 text-neutral-600 italic">{{ $selectedRes->notes }}</div>
                            @endif
                        </div>
                    @endif
                @endif

                {{-- Vehicle & Driver assignment (optional at approve) --}}
                <div class="rounded-[8px] border border-primary-200 bg-primary-50/60 p-3">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-primary-700">Assign Vehicle & Driver (optional — approve first, assign later)</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <x-form-field label="Assign vehicle" type="select" name="assign_vehicle"
                            wire:model="assign_vehicle"
                            :options="array_merge(['' => '— Leave unassigned —'], $vehicleOptions)"
                            :error="$errors->first('assign_vehicle')" />
                        <x-form-field label="Assign driver" type="select" name="assign_driver"
                            wire:model="assign_driver"
                            :options="array_merge(['' => '— Leave unassigned —'], $driverOptions)"
                            :error="$errors->first('assign_driver')" />
                    </div>
                    @if(empty($vehicleOptions))
                        <p class="mt-1 text-xs text-amber-600">⚠ No available vehicles right now.</p>
                    @endif
                    @if(empty($driverOptions))
                        <p class="mt-1 text-xs text-amber-600">⚠ No available drivers right now.</p>
                    @endif
                </div>

                <x-form-field label="Review notes" type="textarea" name="review_notes"
                    wire:model="review_notes" rows="2"
                    :error="$errors->first('review_notes')" />
            </div>

            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                @if($canViewPrediction)
                    <x-btn icon="cpu" :href="route('intelligence.ml')">ML Prediction</x-btn>
                @endif
                <x-btn icon="x" wire:click="rejectReservation" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="rejectReservation">Reject</span>
                    <span wire:loading wire:target="rejectReservation">Rejecting...</span>
                </x-btn>
                <x-btn variant="primary" icon="check" wire:click="approveReservation" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="approveReservation">Approve{{ $assign_vehicle && $assign_driver ? ' & Assign' : '' }}</span>
                    <span wire:loading wire:target="approveReservation">Processing...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- Export Modal --}}
    <x-modal name="export-reservations" title="Export Reservations" icon="download">
        <x-form-field label="Format" type="select" name="format" :options="['CSV' => 'CSV Spreadsheet', 'PDF' => 'PDF Document']" />
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" @click="$dispatch('close-modal')">Download</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
