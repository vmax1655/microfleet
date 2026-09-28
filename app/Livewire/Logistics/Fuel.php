<?php

namespace App\Livewire\Logistics;

use App\Models\Driver;
use App\Models\FuelTransaction;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Support\Rbac;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Fuel Transactions')]
class Fuel extends Component
{
    public string $vehicle = '';
    public string $trip = '';
    public string $driver = '';
    public string $station = 'Petron Malolos';
    public string $receipt = '';
    public float|string $liters = '10.00';
    public float|string $unit_price = '62.10';
    public float|string $odometer = '0';
    public string $fueled_at = '';
    public string $bannerMessage = '';

    public function mount(): void
    {
        $this->fueled_at = now()->toDateString();
        $this->receipt = 'FUEL-' . now()->format('Ymd-His');
        $this->vehicle = Vehicle::orderBy('plate_number')->value('plate_number') ?? '';
        if ($this->vehicle) {
            $v = Vehicle::where('plate_number', $this->vehicle)->first();
            $this->odometer = $v?->current_odometer_km ?? 0;
            $this->unit_price = strtolower($v?->fuel_type ?? '') === 'gasoline' ? '68.50' : '62.10';
        }
    }

    public function openLogFuelModal(): void
    {
        $this->receipt = 'FUEL-' . now()->format('Ymd-His');
        $this->fueled_at = now()->toDateString();
        if (!$this->vehicle) {
            $this->vehicle = Vehicle::orderBy('plate_number')->value('plate_number') ?? '';
        }
        $v = Vehicle::where('plate_number', $this->vehicle)->first();
        if ($v) {
            $this->odometer = $v->current_odometer_km ?? 0;
            $this->unit_price = strtolower($v->fuel_type ?? '') === 'gasoline' ? '68.50' : '62.10';
        }
        $this->resetValidation();
        $this->dispatch('open-modal', 'fuel-entry');
    }

    public function updatedVehicle(): void
    {
        if ($this->vehicle) {
            $v = Vehicle::where('plate_number', $this->vehicle)->first();
            if ($v) {
                $this->odometer = $v->current_odometer_km ?? 0;
                $this->unit_price = strtolower($v->fuel_type ?? '') === 'gasoline' ? '68.50' : '62.10';
            }
        }
    }

    public function updatedTrip(): void
    {
        if ($this->trip) {
            $t = Trip::with(['vehicle', 'driver'])->where('trip_number', $this->trip)->first();
            if ($t) {
                if ($t->vehicle) {
                    $this->vehicle = $t->vehicle->plate_number;
                    $this->odometer = $t->end_odometer_km ?: ($t->start_odometer_km ?: $t->vehicle->current_odometer_km);
                    $this->unit_price = strtolower($t->vehicle->fuel_type ?? '') === 'gasoline' ? '68.50' : '62.10';
                }
                if ($t->driver_id) {
                    $this->driver = (string) $t->driver_id;
                }
            }
        }
    }

    public function saveFuel(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.fuel', 'create'), 403);

        $data = $this->validate([
            'vehicle'    => ['required', 'exists:vehicles,plate_number'],
            'trip'       => ['nullable', 'exists:trips,trip_number'],
            'driver'     => ['nullable', 'exists:drivers,id'],
            'station'    => ['required', 'string', 'max:120'],
            'receipt'    => ['required', 'string', 'max:120', 'unique:fuel_transactions,receipt_number'],
            'liters'     => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0.01'],
            'odometer'   => ['required', 'numeric', 'min:0'],
            'fueled_at'  => ['required', 'date'],
        ], [
            'vehicle.required'    => 'Please select a vehicle.',
            'station.required'    => 'Please enter the fuel station name.',
            'receipt.required'    => 'Please enter the receipt number.',
            'receipt.unique'      => 'This receipt number has already been recorded.',
            'liters.required'     => 'Please specify liters loaded.',
            'unit_price.required' => 'Please enter price per liter.',
            'odometer.required'   => 'Please specify current odometer reading.',
            'fueled_at.required'  => 'Please choose the fuel date.',
        ]);

        $vehicle = Vehicle::where('plate_number', $data['vehicle'])->firstOrFail();
        $trip = $data['trip'] ? Trip::where('trip_number', $data['trip'])->first() : null;

        // Auto-assign driver from form, trip, or vehicle's latest active dispatch
        $driverId = $data['driver'] ?: ($trip?->driver_id ?: $vehicle->dispatches()->latest()->value('driver_id'));

        $totalCost = round((float) $data['liters'] * (float) $data['unit_price'], 2);

        $transaction = FuelTransaction::create([
            'vehicle_id'     => $vehicle->id,
            'driver_id'      => $driverId,
            'trip_id'        => $trip?->id,
            'station_name'   => $data['station'],
            'receipt_number' => $data['receipt'],
            'fuel_type'      => $vehicle->fuel_type ?: 'Diesel',
            'liters'         => $data['liters'],
            'unit_price'     => $data['unit_price'],
            'total_cost'     => $totalCost,
            'odometer_km'    => $data['odometer'],
            'fueled_at'      => Carbon::parse($data['fueled_at']),
            'status'         => 'posted',
        ]);

        if ((float) $data['odometer'] > (float) $vehicle->current_odometer_km) {
            $vehicle->update(['current_odometer_km' => $data['odometer']]);
        }

        $this->bannerMessage = "Fuel transaction {$transaction->receipt_number} (₱" . number_format($totalCost, 2) . ") successfully logged and reflected in the ledger!";
        $this->receipt = 'FUEL-' . now()->format('Ymd-His');
        $this->dispatch('close-modal');
    }

    public function exportCsv()
    {
        $transactions = FuelTransaction::with(['vehicle', 'driver.user', 'trip'])->latest('fueled_at')->get();
        $filename = 'fuel-ledger-' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Receipt No', 'Date', 'Vehicle Plate', 'Driver', 'Trip Number', 'Station', 'Fuel Type', 'Liters', 'Price/Liter', 'Total Cost', 'Odometer (km)', 'Status']);
            foreach ($transactions as $t) {
                fputcsv($file, [
                    $t->receipt_number,
                    $t->fueled_at?->format('Y-m-d'),
                    $t->vehicle?->plate_number,
                    $t->driver?->user?->name ?? 'Unassigned',
                    $t->trip?->trip_number ?? 'N/A',
                    $t->station_name,
                    $t->fuel_type,
                    $t->liters,
                    $t->unit_price,
                    $t->total_cost,
                    $t->odometer_km,
                    $t->status,
                ]);
            }
            fclose($file);
        };

        $this->dispatch('close-modal');
        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $fuelRows = FuelTransaction::query()
            ->with(['vehicle', 'driver.user', 'trip'])
            ->latest('id')
            ->get();

        $vehicleModels = Vehicle::with('type')->orderBy('plate_number')->get();
        $vehicleOptions = $vehicleModels->mapWithKeys(function ($v) {
            $typeName = $v->type?->name ?? 'Vehicle';
            return [$v->plate_number => "{$v->plate_number} ({$typeName})"];
        })->all();

        $tripOptions = Trip::query()->latest()->limit(15)->pluck('trip_number', 'trip_number')->all();

        $driverOptions = Driver::with('user')->get()->mapWithKeys(function ($d) {
            $driverName = $d->user?->name ?? "Driver #{$d->id}";
            return [$d->id => $driverName];
        })->all();

        return view('livewire.logistics.fuel', [
            'fuelRows'        => $fuelRows,
            'vehicleOptions'  => $vehicleOptions,
            'tripOptions'     => $tripOptions,
            'driverOptions'   => $driverOptions,
            'fuelSpendTotal'  => $fuelRows->sum('total_cost'),
            'litersLogged'    => $fuelRows->sum('liters'),
            'reviewCount'     => $fuelRows->whereIn('status', ['for_review', 'For Review'])->count(),
            'canLogFuel'      => Rbac::allowsRoute(auth()->user()?->role, 'logistics.fuel', 'create'),
            'canViewCosts'    => Rbac::allowsRoute(auth()->user()?->role, 'intelligence.costs', 'view'),
        ]);
    }
}
