<?php

namespace App\Livewire\Fleet;

use App\Models\Dispatch as DispatchModel;
use App\Models\Driver;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Support\Rbac;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dispatch Board')]
class Dispatch extends Component
{
    // ── Assignment Modal properties ───────────────────────────────────────────
    public string $reservation = '';
    public string $vehicle = '';
    public string $driver = '';
    public string $assigned_at = '';

    // ── Gate Checkout Modal properties ────────────────────────────────────────
    public string $dispatch = '';
    public float|string $odometer = '0';
    public string $condition = 'Passed';
    public string $time = '';

    // ── Gate Check-in Modal properties ────────────────────────────────────────
    public string $checkin_dispatch = '';
    public float|string $checkin_odometer = '0';
    public string $checkin_condition = 'Good / Clean';
    public string $checkin_time = '';
    public string $checkin_notes = '';

    // ── Detail Modal properties ───────────────────────────────────────────────
    public ?DispatchModel $selectedDispatch = null;
    public ?Reservation $selectedReservation = null;

    // ── UI Feedback ───────────────────────────────────────────────────────────
    public ?string $bannerMessage = null;

    public function mount(): void
    {
        $this->assigned_at = now()->format('Y-m-d\TH:i');
        $this->time        = now()->format('Y-m-d\TH:i');
        $this->checkin_time = now()->format('Y-m-d\TH:i');
    }

    /**
     * Open assignment modal for a specific approved reservation.
     */
    public function openAssignModal(string $reservationNumber): void
    {
        $this->reservation = $reservationNumber;
        $this->assigned_at = now()->format('Y-m-d\TH:i');

        // Suggest a vehicle matching the reservation's vehicle type if available
        $res = Reservation::where('reservation_number', $reservationNumber)->first();
        if ($res) {
            $matchingVehicle = Vehicle::where('status', 'available')
                ->where('vehicle_type_id', $res->vehicle_type_id)
                ->first();
            $this->vehicle = $matchingVehicle?->plate_number ?? (Vehicle::where('status', 'available')->value('plate_number') ?? '');
        }

        $this->driver = Driver::where('availability_status', 'available')
            ->whereDate('license_expires_at', '>=', now()->toDateString())
            ->value('employee_number') ?? '';

        $this->resetValidation();
        $this->dispatch('open-modal', 'dispatch-assignment');
    }

    /**
     * Assign vehicle and driver to approved reservation.
     */
    public function assignDispatch(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'fleet.dispatch', 'create'), 403);

        $data = $this->validate([
            'reservation' => ['required', 'exists:reservations,reservation_number'],
            'vehicle'     => ['required', 'exists:vehicles,plate_number'],
            'driver'      => ['required', 'exists:drivers,employee_number'],
            'assigned_at' => ['required', 'date'],
        ], [
            'reservation.required' => 'Please select an approved reservation.',
            'vehicle.required'     => 'Please select an available vehicle.',
            'driver.required'      => 'Please select an available driver.',
            'assigned_at.required' => 'Please set the assignment time.',
        ]);

        $reservation = Reservation::with('vehicleType')
            ->where('reservation_number', $data['reservation'])
            ->firstOrFail();

        $vehicle = Vehicle::with('type')
            ->where('plate_number', $data['vehicle'])
            ->where('status', 'available')
            ->firstOrFail();

        $driver = Driver::with('user')
            ->where('employee_number', $data['driver'])
            ->where('availability_status', 'available')
            ->whereDate('license_expires_at', '>=', now()->toDateString())
            ->firstOrFail();

        if (! in_array($reservation->status, ['approved', 'pending'])) {
            $this->addError('reservation', 'Only approved reservations can be assigned.');
            return;
        }

        if (! $driver->isQualifiedForVehicle($vehicle->type?->name ?? '')) {
            $this->addError('driver', "Driver {$driver->user?->name} is not licensed for {$vehicle->type?->name} ({$driver->license_restrictions}).");
            return;
        }

        if ($reservation->dispatches()->whereIn('status', ['assigned', 'in_transit'])->exists()) {
            $this->addError('reservation', 'This reservation already has an active dispatch.');
            return;
        }

        // If manager selected a different available vehicle class, update the reservation's vehicle type
        if ($reservation->vehicle_type_id !== $vehicle->vehicle_type_id) {
            $reservation->update(['vehicle_type_id' => $vehicle->vehicle_type_id]);
        }

        $dispatchNum = 'DSP-' . now()->format('Y-') . str_pad(
            DispatchModel::whereYear('created_at', now()->year)->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        $dispatch = DispatchModel::create([
            'dispatch_number'    => $dispatchNum,
            'reservation_id'     => $reservation->id,
            'vehicle_id'         => $vehicle->id,
            'driver_id'          => $driver->id,
            'dispatcher_user_id' => auth()->id(),
            'assigned_at'        => Carbon::parse($data['assigned_at']),
            'status'             => 'assigned',
        ]);

        $reservation->update(['status' => 'assigned']);
        $vehicle->update(['status' => 'assigned']);
        $driver->update(['availability_status' => 'assigned']);

        $this->bannerMessage = "Dispatch {$dispatch->dispatch_number} created! {$vehicle->plate_number} assigned to driver {$driver->user?->name}.";

        $this->reset('reservation', 'vehicle', 'driver');
        $this->assigned_at = now()->format('Y-m-d\TH:i');
        $this->dispatch('close-modal');
    }

    /**
     * Open Gate Checkout modal for an assigned dispatch.
     */
    public function openCheckoutModal(string $dispatchNumber): void
    {
        $this->dispatch = $dispatchNumber;
        $disp = DispatchModel::with('vehicle')->where('dispatch_number', $dispatchNumber)->first();
        if ($disp) {
            $this->odometer = (float) ($disp->vehicle?->current_odometer_km ?? 0);
        }
        $this->condition = 'Passed';
        $this->time      = now()->format('Y-m-d\TH:i');
        $this->resetValidation();
        $this->dispatch('open-modal', 'gate-checkout');
    }

    /**
     * Perform gate checkout: vehicle releases from depot into transit.
     */
    public function gateCheckout(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'fleet.dispatch', 'edit'), 403);

        $data = $this->validate([
            'dispatch'  => ['required', 'exists:dispatches,dispatch_number'],
            'odometer'  => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'in:Passed,Needs Review,Failed'],
            'time'      => ['required', 'date'],
        ], [
            'dispatch.required'  => 'Please select an assigned dispatch.',
            'odometer.required'  => 'Please enter the starting odometer reading.',
            'condition.required' => 'Please select the vehicle inspection condition.',
            'time.required'      => 'Please specify checkout time.',
        ]);

        $dispatch = DispatchModel::with(['reservation.route', 'vehicle', 'driver.user'])
            ->where('dispatch_number', $data['dispatch'])
            ->firstOrFail();

        if ($data['condition'] === 'Failed') {
            $dispatch->vehicle->update(['status' => 'under_maintenance']);
            $dispatch->update(['checkout_condition' => 'failed', 'status' => 'blocked']);
            $this->bannerMessage = "Gate Checkout blocked: {$dispatch->vehicle->plate_number} failed pre-trip inspection and was routed to maintenance.";
            $this->dispatch('close-modal');
            return;
        }

        $dispatch->update([
            'checked_out_at'     => Carbon::parse($data['time']),
            'start_odometer_km'  => $data['odometer'],
            'checkout_condition' => str($data['condition'])->lower()->replace(' ', '_')->toString(),
            'status'             => 'in_transit',
        ]);

        $dispatch->vehicle->update([
            'status'              => 'in_transit',
            'current_odometer_km' => max((float) $dispatch->vehicle->current_odometer_km, (float) $data['odometer']),
        ]);

        $dispatch->driver->update(['availability_status' => 'in_transit']);

        $tripNum = 'TRP-' . now()->format('Y-') . str_pad(
            Trip::whereYear('created_at', now()->year)->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        Trip::updateOrCreate(
            ['dispatch_id' => $dispatch->id],
            [
                'trip_number'       => $tripNum,
                'vehicle_id'        => $dispatch->vehicle_id,
                'driver_id'         => $dispatch->driver_id,
                'route_id'          => $dispatch->reservation?->route_id,
                'departed_at'       => Carbon::parse($data['time']),
                'start_odometer_km' => $data['odometer'],
                'status'            => 'in_transit',
            ]
        );

        $dispatch->reservation?->update(['status' => 'in_transit']);

        $this->bannerMessage = "Gate Checkout complete! {$dispatch->vehicle->plate_number} is now in transit with driver {$dispatch->driver?->user?->name}.";
        $this->dispatch('close-modal');
    }

    /**
     * Open Gate Check-in modal for an in-transit dispatch.
     */
    public function openCheckinModal(string $dispatchNumber): void
    {
        $this->checkin_dispatch = $dispatchNumber;
        $disp = DispatchModel::with('vehicle')->where('dispatch_number', $dispatchNumber)->first();
        if ($disp) {
            $this->checkin_odometer = (float) ($disp->vehicle?->current_odometer_km ?? ($disp->start_odometer_km ?? 0)) + 15;
        }
        $this->checkin_condition = 'Good / Clean';
        $this->checkin_time      = now()->format('Y-m-d\TH:i');
        $this->checkin_notes     = '';
        $this->resetValidation();
        $this->dispatch('open-modal', 'gate-checkin');
    }

    /**
     * Perform gate check-in: vehicle returns to depot, trip completed.
     */
    public function gateCheckin(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'fleet.dispatch', 'edit'), 403);

        $data = $this->validate([
            'checkin_dispatch'  => ['required', 'exists:dispatches,dispatch_number'],
            'checkin_odometer'  => ['required', 'numeric', 'min:0'],
            'checkin_condition' => ['required', 'string'],
            'checkin_time'      => ['required', 'date'],
            'checkin_notes'     => ['nullable', 'string', 'max:500'],
        ], [
            'checkin_dispatch.required' => 'Please select the dispatch to return.',
            'checkin_odometer.required' => 'Please enter the return odometer reading.',
        ]);

        $dispatch = DispatchModel::with(['reservation', 'vehicle', 'driver.user', 'trip'])
            ->where('dispatch_number', $data['checkin_dispatch'])
            ->firstOrFail();

        $startOdo = (float) ($dispatch->start_odometer_km ?? $dispatch->vehicle->current_odometer_km);
        $endOdo   = (float) $data['checkin_odometer'];
        $distance = max(0, $endOdo - $startOdo);

        $dispatch->update(['status' => 'completed']);

        // Update trip
        if ($dispatch->trip) {
            $dispatch->trip->update([
                'arrived_at'      => Carbon::parse($data['checkin_time']),
                'end_odometer_km' => $endOdo,
                'distance_km'     => $distance,
                'status'          => 'completed',
                'return_notes'    => $data['checkin_notes'] ?: "Condition: {$data['checkin_condition']}",
            ]);
        }

        // Return vehicle to available (or maintenance if flagged)
        $vehicleStatus = str_contains(strtolower($data['checkin_condition']), 'maintenance')
            ? 'under_maintenance'
            : 'available';

        $dispatch->vehicle->update([
            'status'              => $vehicleStatus,
            'current_odometer_km' => max((float) $dispatch->vehicle->current_odometer_km, $endOdo),
        ]);

        // Return driver to available
        $dispatch->driver->update(['availability_status' => 'available']);

        // Mark reservation completed
        $dispatch->reservation?->update(['status' => 'completed']);

        $this->bannerMessage = "Gate Check-in complete! {$dispatch->vehicle->plate_number} returned ({$distance} km logged). Status: {$vehicleStatus}.";
        $this->dispatch('close-modal');
    }

    /**
     * View detailed profile of dispatch or reservation.
     */
    public function viewDispatch(string $code): void
    {
        $this->selectedDispatch = DispatchModel::with(['reservation.route', 'vehicle.type', 'driver.user', 'trip'])
            ->where('dispatch_number', $code)
            ->first();

        if (! $this->selectedDispatch) {
            $this->selectedReservation = Reservation::with(['route', 'vehicleType', 'requester'])
                ->where('reservation_number', $code)
                ->first();
        } else {
            $this->selectedReservation = $this->selectedDispatch->reservation;
        }

        $this->dispatch('open-modal', 'dispatch-detail');
    }

    public function render()
    {
        $approvedReservations = Reservation::with(['route', 'vehicleType'])
            ->where('status', 'approved')
            ->get();

        $dispatches = DispatchModel::with(['reservation.route', 'vehicle.type', 'driver.user'])
            ->latest('assigned_at')
            ->get();

        $vehicleOptions = Vehicle::with('type')
            ->where('status', 'available')
            ->orderBy('plate_number')
            ->get()
            ->mapWithKeys(fn ($v) => [$v->plate_number => "{$v->plate_number} — {$v->type?->name}" . ($v->make ? " ({$v->make} {$v->model})" : '')])
            ->all();

        $driverOptions = Driver::with('user')
            ->where('availability_status', 'available')
            ->whereDate('license_expires_at', '>=', now()->toDateString())
            ->orderBy('employee_number')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->employee_number => "{$d->employee_number} — {$d->user?->name} ({$d->license_restrictions})"])
            ->all();

        $assignedDispatchOptions = DispatchModel::with('vehicle')
            ->where('status', 'assigned')
            ->orderByDesc('assigned_at')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->dispatch_number => "{$d->dispatch_number} — {$d->vehicle?->plate_number}"])
            ->all();

        $inTransitDispatchOptions = DispatchModel::with('vehicle')
            ->where('status', 'in_transit')
            ->orderByDesc('checked_out_at')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->dispatch_number => "{$d->dispatch_number} — {$d->vehicle?->plate_number}"])
            ->all();

        return view('livewire.fleet.dispatch', [
            'approvedReservations'     => $approvedReservations,
            'dispatches'               => $dispatches,
            'reservationOptions'       => $approvedReservations->pluck('reservation_number', 'reservation_number')->all(),
            'vehicleOptions'           => $vehicleOptions,
            'driverOptions'            => $driverOptions,
            'dispatchOptions'          => $assignedDispatchOptions,
            'inTransitDispatchOptions' => $inTransitDispatchOptions,
            'canAssignDispatch'        => Rbac::allowsRoute(auth()->user()?->role, 'fleet.dispatch', 'create'),
            'canGateCheckout'          => Rbac::allowsRoute(auth()->user()?->role, 'fleet.dispatch', 'edit'),
            'canViewTrips'             => Rbac::allowsRoute(auth()->user()?->role, 'fleet.trips', 'view'),
            'canViewRoutes'            => Rbac::allowsRoute(auth()->user()?->role, 'logistics.routes', 'view'),
            'canViewFuel'              => Rbac::allowsRoute(auth()->user()?->role, 'logistics.fuel', 'view'),
        ]);
    }
}
