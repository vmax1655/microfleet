@php
    use App\Support\Format;
@endphp

<div>
    <x-breadcrumb />

    <x-page-header title="Maintenance and Repairs" subtitle="Inspection checklists, maintenance alerts, and repair work orders.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-maintenance')">Export</x-btn>
            @if($canCreateInspection)
                <x-btn icon="clipboard-check" @click="$dispatch('open-modal', 'inspection-entry')">Inspection</x-btn>
            @endif
            @if($canCreateWorkOrder)
                <x-btn variant="primary" icon="wrench" @click="$dispatch('open-modal', 'maintenance-entry')">Open Work Order</x-btn>
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
        <x-stat-card label="Open Alerts" :value="number_format($openAlertsCount)" icon="alert-triangle" />
        <x-stat-card label="Critical Holds" :value="number_format($criticalHoldsCount)" icon="shield-check" />
        <x-stat-card label="Open Work Orders" :value="number_format($openWorkOrdersCount)" icon="wrench" />
        <x-stat-card label="Failed Inspections" :value="number_format($failedInspectionsCount)" icon="clipboard-check" />
    </section>

    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
        {{-- Maintenance Alerts table --}}
        <x-card flush>
            <x-slot:title>Maintenance Alerts</x-slot:title>
            <x-slot:subtitle>Inspection failures and mileage thresholds create alerts before vehicles return to dispatch.</x-slot:subtitle>
            <x-data-table sort-key="alert" caption="Maintenance alerts">
                <x-slot:head>
                    <x-th sort="alert">Alert</x-th>
                    <x-th sort="vehicle">Vehicle</x-th>
                    <x-th sort="source">Source</x-th>
                    <x-th sort="severity">Severity</x-th>
                    <x-th sort="status">Status</x-th>
                </x-slot:head>

                @forelse($alerts as $i => $alert)
                    <tr data-row data-alert="{{ $alert->alert_number }}" data-vehicle="{{ $alert->vehicle?->plate_number }}" data-source="{{ $alert->source_type }}" data-severity="{{ $alert->severity }}" data-status="{{ $alert->status }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                        <td class="px-3 py-2.5" data-label="Alert">
                            <span class="block font-medium tabular-nums text-neutral-800">{{ $alert->alert_number }}</span>
                            <span class="block max-w-[18rem] truncate text-xs text-neutral-500">{{ $alert->title }}</span>
                        </td>
                        <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Vehicle">{{ $alert->vehicle?->plate_number ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-neutral-600" data-label="Source">{{ str($alert->source_type)->replace('_', ' ')->title() }}</td>
                        <td class="px-3 py-2.5" data-label="Severity">
                            <x-status-badge :status="str($alert->severity)->title()" :tone="$alert->severity === 'critical' ? 'danger' : 'warning'" />
                        </td>
                        <td class="px-3 py-2.5" data-label="Status"><x-status-badge :status="str($alert->status)->replace('_', ' ')->title()" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-sm text-neutral-500">No maintenance alerts yet.</td>
                    </tr>
                @endforelse
            </x-data-table>
        </x-card>

        {{-- Inspection Checklists table --}}
        <x-card flush>
            <x-slot:title>Inspection Checklists</x-slot:title>
            <x-slot:subtitle>Checklist results are linked to trips, drivers, vehicles, and alert generation.</x-slot:subtitle>
            <x-data-table sort-key="inspection" caption="Inspection checklists">
                <x-slot:head>
                    <x-th sort="inspection">Inspection</x-th>
                    <x-th sort="vehicle">Vehicle</x-th>
                    <x-th sort="driver">Driver</x-th>
                    <x-th sort="items">Checklist</x-th>
                    <x-th sort="result">Result</x-th>
                </x-slot:head>

                @forelse($inspections as $i => $inspection)
                    <tr data-row data-inspection="{{ $inspection->inspection_number }}" data-vehicle="{{ $inspection->vehicle?->plate_number }}" data-driver="{{ $inspection->driver?->user?->name }}" data-result="{{ $inspection->result }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                        <td class="px-3 py-2.5" data-label="Inspection">
                            <span class="block font-medium tabular-nums text-neutral-800">{{ $inspection->inspection_number }}</span>
                            <span class="block text-xs text-neutral-500">{{ str($inspection->inspection_type)->replace('_', ' ')->title() }}</span>
                        </td>
                        <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Vehicle">{{ $inspection->vehicle?->plate_number ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-neutral-600" data-label="Driver">{{ $inspection->driver?->user?->name ?? 'Unassigned' }}</td>
                        <td class="px-3 py-2.5 text-neutral-600" data-label="Checklist">
                            {{ $inspection->items->where('status', 'passed')->count() }}/{{ $inspection->items->count() }} passed
                        </td>
                        <td class="px-3 py-2.5" data-label="Result"><x-status-badge :status="str($inspection->result)->title()" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-sm text-neutral-500">No inspection checklists yet.</td>
                    </tr>
                @endforelse
            </x-data-table>
        </x-card>
    </div>

    {{-- Work Orders table --}}
    <x-card class="mt-6" flush>
        <x-slot:title>Work Orders</x-slot:title>
        <x-slot:subtitle>Open work orders ground vehicles from reservation and dispatch until resolved.</x-slot:subtitle>
        <x-data-table sort-key="wo" caption="Maintenance work orders">
            <x-slot:head>
                <x-th sort="wo">Work Order</x-th>
                <x-th sort="vehicle">Vehicle</x-th>
                <x-th sort="type">Type</x-th>
                <x-th sort="odometer">Odometer</x-th>
                <x-th sort="cost" align="right">Cost</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>
            @forelse($workOrders as $i => $row)
                <tr data-row data-wo="{{ $row->work_order_number }}" data-vehicle="{{ $row->vehicle?->plate_number }}" data-type="{{ $row->service_type }}" data-odometer="{{ $row->odometer_km }}" data-cost="{{ $row->total_cost }}" data-status="{{ $row->status }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td class="px-3 py-2.5 font-medium tabular-nums text-neutral-800" data-label="Work Order">{{ $row->work_order_number }}</td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Vehicle">{{ $row->vehicle?->plate_number ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-neutral-700" data-label="Type">{{ str($row->service_type)->replace('_', ' ')->title() }}</td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Odometer">{{ number_format((float) $row->odometer_km) }} km</td>
                    <td class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800" data-label="Cost">{{ Format::peso($row->total_cost) }}</td>
                    <td class="px-3 py-2.5" data-label="Status"><x-status-badge :status="str($row->status)->title()" /></td>
                    <td class="px-3 py-2.5 text-right" data-label="">
                        @if($canCloseWorkOrder && $row->status === 'open')
                            <x-btn size="sm" icon="check" wire:click="selectWorkOrder('{{ $row->work_order_number }}')" @click="$dispatch('open-modal', 'close-work-order')">Close</x-btn>
                        @else
                            <span class="text-sm text-neutral-400">Completed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-neutral-500">No work orders yet.</td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    {{-- Inspection Entry Modal --}}
    @if($canCreateInspection)
        <x-modal name="inspection-entry" title="Record Inspection" subtitle="Failed checklist items automatically create maintenance alerts." icon="clipboard-check" size="lg">
            <form id="inspection-entry-form" wire:submit="saveInspection" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Vehicle" type="select" name="inspection_vehicle" wire:model.live="inspection_vehicle" :options="$vehicleOptions" :error="$errors->first('inspection_vehicle')" required />
                    <x-form-field label="Inspection type" type="select" name="inspection_type" wire:model="inspection_type" :options="['Pre-trip' => 'Pre-trip', 'Return' => 'Return', 'Yard check' => 'Yard check', 'Preventive' => 'Preventive']" :error="$errors->first('inspection_type')" required />
                    <x-form-field label="Assigned Driver" type="select" name="inspection_driver_id" wire:model="inspection_driver_id" :options="$driverOptions" :error="$errors->first('inspection_driver_id')" />
                    <x-form-field label="Odometer" type="number" name="inspection_odometer" wire:model="inspection_odometer" suffix="km" tabular :error="$errors->first('inspection_odometer')" />
                </div>

                <div class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-3.5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-neutral-600">Checklist Items</span>
                        <span class="text-xs text-neutral-500">Uncheck to mark defect</span>
                    </div>
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <label class="flex items-center gap-2.5 rounded-[6px] border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 cursor-pointer hover:bg-neutral-50">
                            <input type="checkbox" wire:model.live="checklist.lights" class="h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-600">
                            <span class="font-medium">Lights & Signals</span>
                        </label>
                        <label class="flex items-center gap-2.5 rounded-[6px] border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 cursor-pointer hover:bg-neutral-50">
                            <input type="checkbox" wire:model.live="checklist.tires" class="h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-600">
                            <span class="font-medium">Tires & Brakes</span>
                        </label>
                        <label class="flex items-center gap-2.5 rounded-[6px] border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 cursor-pointer hover:bg-neutral-50">
                            <input type="checkbox" wire:model.live="checklist.fluids" class="h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-600">
                            <span class="font-medium">Fluid Levels (Oil/Coolant)</span>
                        </label>
                        <label class="flex items-center gap-2.5 rounded-[6px] border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 cursor-pointer hover:bg-neutral-50">
                            <input type="checkbox" wire:model.live="checklist.documents" class="h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-600">
                            <span class="font-medium">Vehicle Documents & Registration</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Inspection Result" type="select" name="inspection_result" wire:model="inspection_result" :options="['Passed' => 'Passed (Vehicle Ready)', 'Attention' => 'Attention (Minor Follow-up)', 'Failed' => 'Failed (Critical Hold)']" :error="$errors->first('inspection_result')" required />
                    <x-form-field label="Inspection notes" type="textarea" name="inspection_notes" wire:model="inspection_notes" rows="2" placeholder="Remarks or checklist defect details..." :error="$errors->first('inspection_notes')" />
                </div>
            </form>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="check" wire:click="saveInspection" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveInspection">Save Inspection</span>
                    <span wire:loading wire:target="saveInspection">Saving...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- Work Order Entry Modal --}}
    @if($canCreateWorkOrder)
        <x-modal name="maintenance-entry" title="Open Work Order" subtitle="Ground vehicle when maintenance affects dispatch safety." icon="wrench" size="lg">
            <form id="maintenance-entry-form" wire:submit="createWorkOrder" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Vehicle" type="select" name="vehicle" wire:model.live="vehicle" :options="$vehicleOptions" :error="$errors->first('vehicle')" required />
                    <x-form-field label="Service type" type="select" name="service_type" wire:model="service_type" :options="['Preventive' => 'Preventive Maintenance', 'Corrective' => 'Corrective Repair', 'Inspection Failure' => 'Inspection Failure Repair']" :error="$errors->first('service_type')" required />
                    <x-form-field label="Current Odometer" type="number" name="odometer" wire:model="odometer" suffix="km" tabular :error="$errors->first('odometer')" />
                    <x-form-field label="Estimated cost" type="number" name="cost" wire:model="cost" prefix="PHP" tabular :error="$errors->first('cost')" />
                </div>
                <x-form-field label="Service Description" type="textarea" name="description" wire:model="description" rows="3" placeholder="Describe the maintenance needed, replaced components, or repair notes..." :error="$errors->first('description')" />
            </form>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="wrench" wire:click="createWorkOrder" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createWorkOrder">Create Work Order</span>
                    <span wire:loading wire:target="createWorkOrder">Creating...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- Close Work Order Modal --}}
    @if($canCloseWorkOrder)
        <x-modal name="close-work-order" title="Close Work Order" subtitle="Completed maintenance returns eligible vehicles to the dispatch pool." icon="check" tone="success">
            <form id="close-work-order-form" wire:submit="closeWorkOrder" class="space-y-4">
                <x-form-field label="Work order" type="select" name="selected_work_order" wire:model="selected_work_order" :options="$workOrders->where('status', 'open')->pluck('work_order_number', 'work_order_number')->all()" required :error="$errors->first('selected_work_order')" />
                @if($selectedWorkOrder)
                    <div class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-3 text-sm text-neutral-600">
                        <p class="font-medium text-neutral-800">{{ $selectedWorkOrder->vehicle?->plate_number ?? '-' }} - {{ str($selectedWorkOrder->service_type)->replace('_', ' ')->title() }}</p>
                        <p class="mt-1">Opened {{ Format::dateTime($selectedWorkOrder->opened_at) }} · {{ Format::peso($selectedWorkOrder->total_cost) }}</p>
                    </div>
                @endif
                <x-form-field label="Completion notes" type="textarea" name="completion_notes" wire:model="completion_notes" rows="2" placeholder="Summary of repairs performed..." :error="$errors->first('completion_notes')" />
            </form>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="success" icon="check" wire:click="closeWorkOrder" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="closeWorkOrder">Mark Completed</span>
                    <span wire:loading wire:target="closeWorkOrder">Updating...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- Export Modal --}}
    <x-modal name="export-maintenance" title="Export Maintenance Records" icon="download">
        <x-form-field label="Format" type="select" name="format" :options="['CSV' => 'CSV Spreadsheet', 'PDF' => 'PDF Document']" />
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" @click="$dispatch('close-modal')">Download</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
