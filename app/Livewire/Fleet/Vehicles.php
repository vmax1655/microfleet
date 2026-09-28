<?php

namespace App\Livewire\Fleet;

use App\Models\Depot;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Support\Rbac;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Vehicle Registry')]
class Vehicles extends Component
{
    use WithFileUploads;

    public string $category = '';
    public string $search   = '';

    // Registration form fields
    public string $plate        = '';
    public string $type         = '';
    public string $depot        = '';
    public string $fuel         = 'gasoline';
    public float|string $odometer = '0';
    public string $status       = 'available';
    public string $make         = '';
    public string $vehicleModel = '';
    public $image               = null;

    public ?Vehicle $selectedVehicle = null;

    public function mount(): void
    {
        $this->depot = auth()->user()?->branch ?: (Depot::orderBy('name')->value('name') ?? '');
        $this->type  = VehicleType::orderBy('name')->value('name') ?? '';
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function viewVehicle(int $id): void
    {
        $this->selectedVehicle = Vehicle::with(['type', 'depot', 'documents'])->find($id);
        $this->dispatch('open-modal', 'vehicle-detail');
    }

    public function clearImage(): void
    {
        $this->image = null;
    }

    public function saveVehicle(): void
    {
        abort_unless(auth()->user()?->allows('fleet-vehicle', 'create'), 403);

        $data = $this->validate([
            'plate'        => ['required', 'string', 'max:30', 'unique:vehicles,plate_number'],
            'type'         => ['required', 'exists:vehicle_types,name'],
            'depot'        => ['required', 'exists:depots,name'],
            'fuel'         => ['required', 'string', 'max:40'],
            'odometer'     => ['required', 'numeric', 'min:0'],
            'status'       => ['required', 'in:available,inactive'],
            'make'         => ['nullable', 'string', 'max:60'],
            'vehicleModel' => ['nullable', 'string', 'max:60'],
            'image'        => ['nullable', 'image', 'max:5120'],
        ]);

        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->store('vehicles', 'public');
        }

        Vehicle::create([
            'depot_id'            => Depot::where('name', $data['depot'])->value('id'),
            'vehicle_type_id'     => VehicleType::where('name', $data['type'])->value('id'),
            'plate_number'        => strtoupper($data['plate']),
            'image_path'          => $imagePath,
            'fuel_type'           => $data['fuel'],
            'make'                => $data['make'] ?: null,
            'model'               => $data['vehicleModel'] ?: null,
            'current_odometer_km' => $data['odometer'],
            'status'              => $data['status'],
            'acquisition_date'    => now()->toDateString(),
        ]);

        $this->reset(['plate', 'odometer', 'image', 'make', 'vehicleModel']);
        $this->depot  = auth()->user()?->branch ?: (Depot::orderBy('name')->value('name') ?? '');
        $this->type   = VehicleType::orderBy('name')->value('name') ?? '';
        $this->fuel   = 'gasoline';
        $this->status = 'available';
        $this->dispatch('close-modal');
    }

    public function render()
    {
        $allVehicles = Vehicle::with(['type', 'depot', 'documents'])->get();

        $query = Vehicle::with(['type', 'depot', 'documents'])->orderBy('plate_number');

        if ($this->category) {
            $query->whereHas('type', fn ($q) => $q->where('name', $this->category));
        }

        if ($this->search) {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('plate_number', 'like', $term)
                  ->orWhere('make', 'like', $term)
                  ->orWhere('model', 'like', $term);
            });
        }

        $categories = VehicleType::orderBy('name')->get();

        return view('livewire.fleet.vehicles', [
            'vehicles'       => $query->get(),
            'totalCount'     => $allVehicles->count(),
            'availableCount' => $allVehicles->where('status', 'available')->count(),
            'reservedCount'  => $allVehicles->whereIn('status', ['reserved', 'assigned'])->count(),
            'maintenanceCount' => $allVehicles->where('status', 'under_maintenance')->count(),
            'docsDueCount'   => $allVehicles->flatMap->documents->where('status', 'due_soon')->count(),
            'categories'     => $categories,
            'categoryCounts' => $allVehicles->groupBy('vehicle_type_id')->map->count(),
            'typeOptions'    => $categories->pluck('name')->all(),
            'depotOptions'   => Depot::orderBy('name')->pluck('name')->all(),
            'canCreateVehicles' => auth()->user()?->allows('fleet-vehicle', 'create') ?? false,
            'canViewDispatch' => Rbac::allowsRoute(auth()->user()?->role, 'fleet.dispatch', 'view'),
            'canViewFuel' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.fuel', 'view'),
            'canViewMaintenance' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.maintenance', 'view'),
        ]);
    }
}
