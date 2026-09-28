<?php

namespace App\Livewire\Fleet;

use App\Models\MaintenanceAlert;
use App\Models\TransportCost;
use App\Models\Trip;
use App\Support\MlInsights;
use App\Support\Rbac;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Trip Monitoring')]
class Trips extends Component
{
    public string $trip = '';
    public float|string $end_odometer = '';
    public float $start_odometer = 0;
    public float $planned_distance = 0;
    public string $returned_at = '';
    public string $condition = 'Passed';
    public string $notes = '';
    public string $selectedTripStatus = 'in_transit';

    // Selected trip metadata for modal preview
    public ?string $selectedVehicle = null;
    public ?string $selectedDriver = null;
    public ?string $selectedRoute = null;

    // Feedback banner
    public bool $showBanner = false;
    public string $bannerType = 'success';
    public string $bannerMessage = '';

    public function mount(): void
    {
        $this->returned_at = now()->format('Y-m-d\TH:i');
        $this->selectFirstAvailableTrip();
    }

    public function selectFirstAvailableTrip(): void
    {
        $query = Trip::with(['route', 'vehicle', 'driver.user'])->latest();

        if (auth()->user()?->role === 'Driver') {
            $driverId = auth()->user()?->driver?->id;
            $query->where('driver_id', $driverId);
        }

        // Prefer an in-transit trip first; if none, take the latest trip
        $inTransit = (clone $query)->where('status', 'in_transit')->first();
        $target = $inTransit ?? $query->first();

        if ($target) {
            $this->applyTripSelection($target);
        } else {
            $this->trip = '';
            $this->start_odometer = 0;
            $this->planned_distance = 0;
            $this->end_odometer = '';
            $this->selectedTripStatus = 'in_transit';
            $this->selectedVehicle = null;
            $this->selectedDriver = null;
            $this->selectedRoute = null;
        }
    }

    public function applyTripSelection(Trip $trip): void
    {
        $this->trip = $trip->trip_number;
        $this->selectedTripStatus = $trip->status;
        $this->start_odometer = (float) ($trip->start_odometer_km ?? 0);
        $this->planned_distance = (float) ($trip->route?->planned_distance_km ?? 25.0);

        if ($trip->status === 'completed' && $trip->end_odometer_km) {
            $this->end_odometer = (string) $trip->end_odometer_km;
        } else {
            $this->end_odometer = (string) round($this->start_odometer + $this->planned_distance, 1);
        }

        $this->selectedVehicle = $trip->vehicle?->plate_number;
        $this->selectedDriver = $trip->driver?->user?->name;
        $this->selectedRoute = $trip->route?->name;
        $this->notes = $trip->return_notes ?? '';
    }

    public function openCheckinModal(?string $tripNumber = null): void
    {
        if ($tripNumber) {
            $trip = Trip::with(['route', 'vehicle', 'driver.user'])->where('trip_number', $tripNumber)->first();
            if ($trip) {
                $this->applyTripSelection($trip);
            }
        } elseif (empty($this->trip)) {
            $this->selectFirstAvailableTrip();
        }

        $this->returned_at = now()->format('Y-m-d\TH:i');
        $this->condition = 'Passed';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'trip-checkin');
    }

    public function updatedTrip(string $tripNumber): void
    {
        $trip = Trip::with(['route', 'vehicle', 'driver.user'])->where('trip_number', $tripNumber)->first();
        if ($trip) {
            $this->applyTripSelection($trip);
        }
    }

    public function checkInTrip(): void
    {
        $allowed = auth()->user()?->allows('driver-trip', 'edit')
            || Rbac::allowsRoute(auth()->user()?->role, 'fleet.trips', 'edit');

        abort_unless($allowed, 403);

        $trip = Trip::with([
            'vehicle.type',
            'driver',
            'fuelTransactions',
            'expenses',
            'route',
            'dispatch.reservation',
        ])->where('trip_number', $this->trip)->first();

        if (! $trip) {
            $this->addError('trip', 'Selected trip not found.');
            return;
        }

        $start = (float) ($trip->start_odometer_km ?? 0);

        $data = $this->validate([
            'trip' => ['required', 'exists:trips,trip_number'],
            'end_odometer' => ['required', 'numeric', 'gte:'.$start],
            'returned_at' => ['required', 'date'],
            'condition' => ['required', 'in:Passed,Needs Maintenance,Incident Reported'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'end_odometer.gte' => 'Ending odometer must be at least '.number_format($start, 1).' km (starting odometer: '.number_format($start).' km).',
            'end_odometer.required' => 'Ending odometer reading is required.',
        ]);

        $end = (float) $data['end_odometer'];
        $distance = max(0, round($end - $start, 2));

        $trip->update([
            'arrived_at' => Carbon::parse($data['returned_at']),
            'end_odometer_km' => $end,
            'distance_km' => $distance,
            'status' => 'completed',
            'return_notes' => $data['notes'],
        ]);

        $trip->vehicle?->update([
            'current_odometer_km' => $end,
            'status' => $data['condition'] === 'Passed' ? 'available' : 'under_maintenance',
        ]);

        $trip->driver?->update(['availability_status' => 'available']);
        $trip->dispatch?->update(['status' => 'completed']);
        $trip->dispatch?->reservation?->update(['status' => 'completed']);

        // Maintenance allocation rate based on vehicle type
        $maintRate = match ($trip->vehicle?->type?->name) {
            'Motorcycle' => 1.25,
            'Passenger Van' => 2.50,
            'Utility Pickup' => 2.75,
            'Light Truck' => 3.25,
            default => 2.25,
        };

        $fuelCost = (float) $trip->fuelTransactions->sum('total_cost');
        $expenseCost = (float) $trip->expenses
            ->whereIn('approval_status', ['approved', 'checked', 'pending'])
            ->sum('amount');
        $maintenance = round($distance * $maintRate, 2);
        $total = round($fuelCost + $expenseCost + $maintenance, 2);

        TransportCost::updateOrCreate(
            ['trip_id' => $trip->id],
            [
                'fuel_cost' => $fuelCost,
                'expense_cost' => $expenseCost,
                'maintenance_allocation' => $maintenance,
                'total_cost' => $total,
                'cost_per_km' => $distance > 0 ? round($total / $distance, 2) : null,
                'center_code' => $trip->route?->center_code,
                'status' => 'posted',
            ]
        );

        if ($data['condition'] !== 'Passed') {
            MaintenanceAlert::create([
                'vehicle_id' => $trip->vehicle_id,
                'alert_number' => 'MAL-'.now()->format('Ymd-His'),
                'source_type' => 'return_checkin',
                'severity' => $data['condition'] === 'Incident Reported' ? 'critical' : 'medium',
                'title' => 'Return check-in requires maintenance review',
                'description' => $data['notes'] ?: "Condition reported as {$data['condition']} upon return check-in.",
                'status' => 'open',
                'triggered_at' => now(),
            ]);
        }

        // Reconcile ML prediction with actual trip fuel & cost
        try {
            MlInsights::reconcileTrip($trip);
        } catch (\Throwable) {
            // Non-critical
        }

        $this->dispatch('close-modal', 'trip-checkin');

        // Reset checkin form selection
        $this->selectFirstAvailableTrip();

        $this->showBanner = true;
        $this->bannerType = 'success';
        $this->bannerMessage = sprintf(
            'Trip %s check-in recorded successfully! Distance: %s km. Vehicle %s status: %s.',
            $trip->trip_number,
            number_format($distance, 1),
            $trip->vehicle?->plate_number ?? 'assigned',
            $data['condition'] === 'Passed' ? 'Available' : 'Under Maintenance'
        );
    }

    public function exportCsv(): StreamedResponse
    {
        $trips = Trip::with(['route.depot', 'driver.user', 'vehicle', 'transportCost'])->latest()->get();

        return response()->streamDownload(function () use ($trips) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Trip Number', 'Status', 'Route', 'Driver', 'Vehicle',
                'Start Odometer (km)', 'End Odometer (km)', 'Distance (km)',
                'Fuel Cost (PHP)', 'Expense Cost (PHP)', 'Maintenance Allocation (PHP)', 'Total Cost (PHP)',
                'Cost / km', 'Departed At', 'Arrived At', 'Return Notes',
            ]);

            foreach ($trips as $t) {
                fputcsv($out, [
                    $t->trip_number,
                    str($t->status)->replace('_', ' ')->title(),
                    $t->route?->name ?? '',
                    $t->driver?->user?->name ?? '',
                    $t->vehicle?->plate_number ?? '',
                    $t->start_odometer_km,
                    $t->end_odometer_km,
                    $t->distance_km,
                    $t->transportCost?->fuel_cost,
                    $t->transportCost?->expense_cost,
                    $t->transportCost?->maintenance_allocation,
                    $t->transportCost?->total_cost,
                    $t->transportCost?->cost_per_km,
                    $t->departed_at?->format('Y-m-d H:i:s'),
                    $t->arrived_at?->format('Y-m-d H:i:s'),
                    $t->return_notes,
                ]);
            }

            fclose($out);
        }, 'trips-ledger-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function dismissBanner(): void
    {
        $this->showBanner = false;
    }

    public function render()
    {
        $query = Trip::with(['route.depot', 'driver.user', 'vehicle', 'transportCost'])->latest();

        if (auth()->user()?->role === 'Driver') {
            $driverId = auth()->user()?->driver?->id;
            $query->where('driver_id', $driverId);
        }

        $allTrips = (clone $query)->get();

        $inTransitTrips = $allTrips->where('status', 'in_transit');
        $completedTrips = $allTrips->where('status', 'completed');

        $tripOptions = [];
        foreach ($inTransitTrips as $t) {
            $tripOptions[$t->trip_number] = "⚡ [IN TRANSIT] {$t->trip_number} — {$t->vehicle?->plate_number} ({$t->route?->name})";
        }
        foreach ($completedTrips as $t) {
            $tripOptions[$t->trip_number] = "✓ [COMPLETED] {$t->trip_number} — {$t->vehicle?->plate_number} ({$t->route?->name})";
        }

        return view('livewire.fleet.trips', [
            'trips' => $allTrips,
            'tripOptions' => $tripOptions,
            'canEditTrips' => auth()->user()?->allows('driver-trip', 'edit')
                || Rbac::allowsRoute(auth()->user()?->role, 'fleet.trips', 'edit'),
            'canViewCosts' => Rbac::allowsRoute(auth()->user()?->role, 'intelligence.costs', 'view'),
        ]);
    }
}
