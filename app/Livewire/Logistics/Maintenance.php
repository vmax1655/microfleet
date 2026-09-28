<?php

namespace App\Livewire\Logistics;

use App\Models\Driver;
use App\Models\Inspection;
use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\Vehicle;
use App\Support\Rbac;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Maintenance and Repairs')]
class Maintenance extends Component
{
    // Inspection form properties
    public string $inspection_vehicle = '';
    public string $inspection_type = 'Pre-trip';
    public int|string|null $inspection_driver_id = null;
    public float|string $inspection_odometer = '0';
    public string $inspection_result = 'Passed';
    public string $inspection_notes = '';
    public array $checklist = [
        'lights' => true,
        'tires' => true,
        'fluids' => true,
        'documents' => true,
    ];

    // Work order form properties
    public string $vehicle = '';
    public string $service_type = 'Preventive';
    public float|string $odometer = '0';
    public float|string $cost = '2500.00';
    public string $description = '';

    // Close work order properties
    public string $completion_notes = 'Service completed and road test passed.';
    public string $selected_work_order = '';

    // Flash notification
    public ?string $bannerMessage = null;

    public function mount(): void
    {
        $firstVehicle = Vehicle::query()->orderBy('plate_number')->first();
        if ($firstVehicle) {
            $this->inspection_vehicle = $firstVehicle->plate_number;
            $this->inspection_odometer = (string) (float) $firstVehicle->current_odometer_km;
            $this->vehicle = $firstVehicle->plate_number;
            $this->odometer = (string) (float) $firstVehicle->current_odometer_km;
        }

        $this->inspection_driver_id = Driver::query()->value('id');
    }

    public function updatedInspectionVehicle(string $plate): void
    {
        $vehicle = Vehicle::where('plate_number', $plate)->first();
        if ($vehicle) {
            $this->inspection_odometer = (string) (float) $vehicle->current_odometer_km;
        }
    }

    public function updatedVehicle(string $plate): void
    {
        $vehicle = Vehicle::where('plate_number', $plate)->first();
        if ($vehicle) {
            $this->odometer = (string) (float) $vehicle->current_odometer_km;
        }
    }

    public function updatedChecklist(): void
    {
        $allPassed = ! in_array(false, $this->checklist, true);
        if ($allPassed) {
            $this->inspection_result = 'Passed';
        } else {
            $failedCount = count(array_filter($this->checklist, fn ($v) => ! $v));
            $this->inspection_result = $failedCount > 1 ? 'Failed' : 'Attention';
        }
    }

    public function saveInspection(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.maintenance', 'create'), 403);

        $data = $this->validate([
            'inspection_vehicle' => ['required', 'exists:vehicles,plate_number'],
            'inspection_type' => ['required', 'string', 'max:80'],
            'inspection_driver_id' => ['nullable', 'exists:drivers,id'],
            'inspection_odometer' => ['nullable', 'numeric', 'min:0'],
            'inspection_result' => ['required', 'in:Passed,Attention,Failed'],
            'inspection_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'inspection_vehicle.required' => 'Please select a vehicle to inspect.',
            'inspection_type.required' => 'Please select an inspection type.',
            'inspection_result.required' => 'Please select an inspection result.',
        ]);

        $vehicle = Vehicle::where('plate_number', $data['inspection_vehicle'])->firstOrFail();
        $result = str($data['inspection_result'])->lower()->toString();
        $odometer = ! empty($data['inspection_odometer']) ? (float) $data['inspection_odometer'] : (float) $vehicle->current_odometer_km;

        $inspection = Inspection::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => ! empty($data['inspection_driver_id']) ? (int) $data['inspection_driver_id'] : null,
            'inspection_number' => 'INS-'.now()->format('Ymd-His'),
            'inspection_type' => str($data['inspection_type'])->lower()->replace(' ', '_')->toString(),
            'result' => $result,
            'odometer_km' => $odometer,
            'notes' => $data['inspection_notes'] ?? null,
            'inspected_at' => now(),
        ]);

        $itemsMap = [
            'lights' => 'Lights and signals',
            'tires' => 'Tires and brakes',
            'fluids' => 'Fluid levels',
            'documents' => 'Vehicle documents',
        ];

        foreach ($itemsMap as $key => $itemName) {
            $isPassed = ! empty($this->checklist[$key]);
            $inspection->items()->create([
                'item_name' => $itemName,
                'status' => $isPassed ? 'passed' : 'failed',
                'remarks' => $isPassed ? 'Checked and passed' : 'Issue detected during inspection',
            ]);
        }

        // Update vehicle odometer if provided odometer is higher
        if ($odometer > (float) $vehicle->current_odometer_km) {
            $vehicle->update(['current_odometer_km' => $odometer]);
        }

        // If inspection failed or requires attention, generate alert and flag vehicle
        if (in_array($result, ['attention', 'failed'], true)) {
            $vehicle->update(['status' => 'under_maintenance']);

            MaintenanceAlert::create([
                'vehicle_id' => $vehicle->id,
                'inspection_id' => $inspection->id,
                'alert_number' => 'MAL-'.now()->format('Ymd-His'),
                'source_type' => 'inspection_failure',
                'severity' => $result === 'failed' ? 'critical' : 'medium',
                'title' => 'Inspection failure: '.$vehicle->plate_number.' requires review',
                'description' => $data['inspection_notes'] ?: 'Checklist failure requires maintenance inspection.',
                'status' => 'open',
                'triggered_at' => now(),
            ]);
        } elseif ($result === 'passed' && $vehicle->status === 'under_maintenance') {
            $vehicle->update(['status' => 'available']);
        }

        $this->bannerMessage = 'Inspection '.$inspection->inspection_number.' for '.$vehicle->plate_number.' recorded successfully!';
        $this->dispatch('close-modal');

        // Reset inspection form
        $this->inspection_notes = '';
        $this->checklist = ['lights' => true, 'tires' => true, 'fluids' => true, 'documents' => true];
        $this->inspection_result = 'Passed';
    }

    public function createWorkOrder(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.maintenance', 'create'), 403);

        $data = $this->validate([
            'vehicle' => ['required', 'exists:vehicles,plate_number'],
            'service_type' => ['required', 'string', 'max:80'],
            'odometer' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'vehicle.required' => 'Please select a vehicle.',
            'service_type.required' => 'Please select a service type.',
        ]);

        $vehicle = Vehicle::where('plate_number', $data['vehicle'])->firstOrFail();
        $cost = (float) ($data['cost'] ?: 0);
        $odometer = ! empty($data['odometer']) ? (float) $data['odometer'] : (float) $vehicle->current_odometer_km;

        $wo = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'work_order_number' => 'WO-'.now()->format('Ymd-His'),
            'service_type' => str($data['service_type'])->lower()->replace(' ', '_')->toString(),
            'description' => $data['description'] ?: 'Scheduled maintenance for '.$vehicle->plate_number,
            'odometer_km' => $odometer,
            'parts_cost' => round($cost * 0.6, 2),
            'labor_cost' => round($cost * 0.4, 2),
            'total_cost' => $cost,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $vehicle->update(['status' => 'under_maintenance']);

        if ($odometer > (float) $vehicle->current_odometer_km) {
            $vehicle->update(['current_odometer_km' => $odometer]);
        }

        $this->bannerMessage = 'Work Order '.$wo->work_order_number.' opened for '.$vehicle->plate_number.'!';
        $this->dispatch('close-modal');

        // Reset form
        $this->description = '';
    }

    public function selectWorkOrder(string $workOrderNumber): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.maintenance', 'edit'), 403);

        $workOrder = MaintenanceRecord::where('work_order_number', $workOrderNumber)
            ->where('status', 'open')
            ->firstOrFail();

        $this->selected_work_order = $workOrder->work_order_number;
    }

    public function closeWorkOrder(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.maintenance', 'edit'), 403);

        $data = $this->validate([
            'selected_work_order' => ['required', 'exists:maintenance_records,work_order_number'],
            'completion_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $workOrder = MaintenanceRecord::where('work_order_number', $data['selected_work_order'])
            ->where('status', 'open')
            ->first();

        if (! $workOrder) {
            $this->addError('selected_work_order', 'Select an open work order to close.');
            return;
        }

        $workOrder->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
            'description' => trim(($workOrder->description ?? '')."\n".$this->completion_notes),
        ]);

        $workOrder->alerts()->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        $workOrder->vehicle?->update(['status' => 'available']);
        $this->bannerMessage = 'Work Order '.$workOrder->work_order_number.' completed! Vehicle '.$workOrder->vehicle?->plate_number.' returned to available pool.';
        $this->selected_work_order = '';
        $this->dispatch('close-modal');
    }

    public function render()
    {
        $vehicles = Vehicle::query()->orderBy('plate_number')->get();

        return view('livewire.logistics.maintenance', [
            'alerts' => MaintenanceAlert::query()
                ->with(['vehicle', 'inspection'])
                ->latest('triggered_at')
                ->limit(10)
                ->get(),
            'inspections' => Inspection::query()
                ->with(['vehicle', 'driver.user', 'trip', 'items'])
                ->latest('inspected_at')
                ->limit(10)
                ->get(),
            'workOrders' => MaintenanceRecord::query()
                ->with(['vehicle', 'alerts'])
                ->latest('opened_at')
                ->limit(15)
                ->get(),
            'openAlertsCount' => MaintenanceAlert::whereIn('status', ['open', 'monitoring'])->count(),
            'criticalHoldsCount' => MaintenanceAlert::where('severity', 'critical')->count(),
            'openWorkOrdersCount' => MaintenanceRecord::where('status', 'open')->count(),
            'failedInspectionsCount' => Inspection::where('result', 'failed')->count(),
            'vehicleOptions' => $vehicles->mapWithKeys(fn ($v) => [
                $v->plate_number => $v->plate_number.' · '.$v->make.' '.$v->model.' ('.str($v->status)->replace('_', ' ')->title().')',
            ])->all(),
            'driverOptions' => ['' => 'No driver assigned'] + Driver::with('user')->get()->mapWithKeys(fn ($d) => [
                $d->id => $d->user?->name ?? 'Driver #'.$d->id,
            ])->all(),
            'canCreateInspection' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.maintenance', 'create'),
            'canCreateWorkOrder' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.maintenance', 'create'),
            'canCloseWorkOrder' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.maintenance', 'edit'),
            'selectedWorkOrder' => $this->selected_work_order
                ? MaintenanceRecord::where('work_order_number', $this->selected_work_order)->first()
                : null,
        ]);
    }
}
