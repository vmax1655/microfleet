<?php

namespace App\Livewire\Fleet;

use App\Models\Depot;
use App\Models\Dispatch;
use App\Models\Driver;
use App\Models\Reservation;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Support\MlInsights;
use App\Support\Rbac;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reservations')]
class Reservations extends Component
{
    // ── New Reservation form ──────────────────────────────────────────────────
    public string $purpose          = 'Center Collection';
    public string $origin_depot     = '';
    public string $route            = '';
    public string $vehicle_type     = '';
    public int|string $passengers   = 1;
    public string $load_level       = 'light';
    public string $start            = '';
    public string $end              = '';
    public string $notes            = '';
    public int|string $preferred_driver = '';

    // ── Review / Approve modal ────────────────────────────────────────────────
    public string $review_reservation = '';
    public string $review_notes       = '';
    public int|string $assign_vehicle = '';
    public int|string $assign_driver  = '';

    // ── UI feedback ───────────────────────────────────────────────────────────
    public ?string $bannerMessage = null;

    public function mount(): void
    {
        $this->start = now()->addDay()->setTime(7, 30)->format('Y-m-d\TH:i');
        $this->end   = now()->addDay()->setTime(11, 30)->format('Y-m-d\TH:i');

        $userBranch = auth()->user()?->branch;
        $this->origin_depot = Depot::when($userBranch, fn ($q) => $q->where('name', $userBranch))
            ->orderBy('name')
            ->value('name') ?? (Depot::orderBy('name')->value('name') ?? '');

        $options = $this->loadDestinationOptions();
        $this->route = array_key_first($options) ?? '';

        $this->vehicle_type = VehicleType::orderBy('name')->value('name') ?? '';
    }

    /** When depot changes, reload destinations and set Point B to first available destination. */
    public function updatedOriginDepot(): void
    {
        $options = $this->loadDestinationOptions();
        $this->route = array_key_first($options) ?? '';
    }

    /**
     * Load destinations for the currently selected origin depot.
     * Ensures local field centers and inter-depot branch choices always exist.
     */
    public function loadDestinationOptions(): array
    {
        if (empty($this->origin_depot)) {
            $this->origin_depot = Depot::orderBy('name')->value('name') ?? '';
        }

        $depot = Depot::where('name', $this->origin_depot)->first();
        if ($depot) {
            $this->ensureDepotHasRoutes($depot);
        }

        $routes = TransportRoute::with('depot')
            ->when($this->origin_depot, function ($query) {
                $query->whereHas('depot', fn ($d) => $d->where('name', $this->origin_depot));
            })
            ->orderBy('route_code')
            ->get();

        $options = [];
        $seenDestinations = [];

        // 1. Depot-specific routes (centers, collection units)
        foreach ($routes as $r) {
            $options[$r->route_code] = "{$r->center_code} — {$r->destination_name}";
            $seenDestinations[strtolower(trim($r->destination_name))] = true;
        }

        // 2. Inter-depot destination options (all other branch depots)
        $otherDepots = Depot::where('name', '!=', $this->origin_depot)->orderBy('name')->get();
        foreach ($otherDepots as $other) {
            $destName = "{$other->name} (Inter-Depot)";
            if (isset($seenDestinations[strtolower(trim($destName))]) || isset($seenDestinations[strtolower(trim($other->name))])) {
                continue;
            }

            $abbrFrom  = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $depot?->name ?? 'DEP'), 0, 3));
            $abbrTo    = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $other->name), 0, 3));
            $interCode = "RTE-{$abbrFrom}-{$abbrTo}";

            $interRoute = TransportRoute::firstOrCreate(
                ['route_code' => $interCode],
                [
                    'origin_depot_id'            => $depot?->id,
                    'name'                       => "{$this->origin_depot} to {$other->name} Transfer",
                    'center_code'                => "DEP-{$abbrTo}",
                    'destination_name'           => $destName,
                    'destination_latitude'       => $other->latitude,
                    'destination_longitude'      => $other->longitude,
                    'planned_distance_km'        => 35.0,
                    'estimated_duration_minutes' => 50,
                    'road_profile'               => 'expressway',
                    'status'                     => 'active',
                ]
            );
            $options[$interRoute->route_code] = "{$interRoute->center_code} — {$interRoute->destination_name}";
            $seenDestinations[strtolower(trim($destName))] = true;
        }

        return $options;
    }

    /**
     * Automatically ensure a depot has operational field routes.
     */
    private function ensureDepotHasRoutes(Depot $depot): void
    {
        $existingCount = TransportRoute::where('origin_depot_id', $depot->id)->count();
        if ($existingCount > 0) {
            return;
        }

        $abbr = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $depot->name), 0, 3));
        if (strlen($abbr) < 2) $abbr = 'DEP';

        $cleanCity = explode(',', $depot->address ?: $depot->name)[0];

        $defaultRoutes = [
            [
                'code'        => "RTE-{$abbr}-01",
                'name'        => "{$depot->name} - {$cleanCity} Microfinance Center",
                'center'      => "CTR-{$abbr}-01",
                'destination' => "{$cleanCity} Lending & Collection Center 01",
                'distance'    => 8.5,
                'duration'    => 25,
            ],
            [
                'code'        => "RTE-{$abbr}-02",
                'name'        => "{$depot->name} - {$cleanCity} Field Barangay Office",
                'center'      => "CTR-{$abbr}-02",
                'destination' => "{$cleanCity} Field Barangay Unit 02",
                'distance'    => 14.2,
                'duration'    => 35,
            ],
            [
                'code'        => "RTE-{$abbr}-03",
                'name'        => "{$depot->name} - {$cleanCity} Commercial District Hub",
                'center'      => "CTR-{$abbr}-03",
                'destination' => "{$cleanCity} District Commercial Hub",
                'distance'    => 19.8,
                'duration'    => 45,
            ],
        ];

        foreach ($defaultRoutes as $dr) {
            TransportRoute::firstOrCreate(
                ['route_code' => $dr['code']],
                [
                    'origin_depot_id'            => $depot->id,
                    'name'                       => $dr['name'],
                    'center_code'                => $dr['center'],
                    'destination_name'           => $dr['destination'],
                    'destination_latitude'       => $depot->latitude,
                    'destination_longitude'      => $depot->longitude,
                    'planned_distance_km'        => $dr['distance'],
                    'estimated_duration_minutes' => $dr['duration'],
                    'road_profile'               => 'mixed',
                    'status'                     => 'active',
                ]
            );
        }
    }

    /** Pre-fill the review form when a reservation number is selected from the table. */
    public function openReview(string $reservationNumber): void
    {
        $this->review_reservation = $reservationNumber;
        $this->review_notes       = '';
        $this->assign_vehicle     = '';
        $this->assign_driver      = '';

        $res = Reservation::where('reservation_number', $reservationNumber)->first();
        if ($res) {
            if ($res->preferred_driver_id) {
                $this->assign_driver = $res->preferred_driver_id;
            }
            $matchingVehicle = Vehicle::where('status', 'available')
                ->where('vehicle_type_id', $res->vehicle_type_id)
                ->first();
            if ($matchingVehicle) {
                $this->assign_vehicle = $matchingVehicle->id;
            }
        }

        $this->dispatch('open-modal', 'review-reservation');
    }

    public function saveReservation(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'fleet.reservations', 'create'), 403);

        $data = $this->validate([
            'purpose'          => ['required', 'string', 'max:80'],
            'route'            => ['required', 'exists:routes,route_code'],
            'vehicle_type'     => ['required', 'exists:vehicle_types,name'],
            'passengers'       => ['required', 'integer', 'min:1', 'max:30'],
            'load_level'       => ['required', 'in:light,medium,heavy'],
            'start'            => ['required', 'date'],
            'end'              => ['required', 'date', 'after:start'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'preferred_driver' => ['nullable', 'exists:drivers,id'],
        ], [
            'route.required'        => 'Please select a destination route.',
            'route.exists'          => 'The selected route is not valid.',
            'vehicle_type.required' => 'Please select a vehicle class.',
            'start.required'        => 'Please set the start date and time.',
            'end.after'             => 'The end time must be after the start time.',
        ]);

        $route = TransportRoute::where('route_code', $data['route'])->firstOrFail();
        $type  = VehicleType::where('name', $data['vehicle_type'])->firstOrFail();

        $reservation = Reservation::create([
            'reservation_number'  => 'RSV-' . now()->format('Y-') . str_pad(
                Reservation::whereYear('created_at', now()->year)->count() + 1,
                4, '0', STR_PAD_LEFT
            ),
            'requester_user_id'   => auth()->id(),
            'route_id'            => $route->id,
            'vehicle_type_id'     => $type->id,
            'preferred_driver_id' => $data['preferred_driver'] ?: null,
            'purpose'             => $data['purpose'],
            'passenger_count'     => $data['passengers'],
            'load_level'          => $data['load_level'],
            'scheduled_start_at'  => Carbon::parse($data['start']),
            'scheduled_end_at'    => Carbon::parse($data['end']),
            'status'              => 'pending',
            'notes'               => $data['notes'] ?? null,
        ]);

        try {
            $prediction = MlInsights::generateForReservation($reservation);
            $reservation->update([
                'predicted_fuel_liters' => $prediction->predicted_fuel_liters,
                'predicted_cost'        => $prediction->predicted_cost,
            ]);
        } catch (\Throwable) {
            // Prediction failure should not block the reservation
        }

        $this->bannerMessage = "Reservation {$reservation->reservation_number} submitted for approval.";
        $this->resetForm();
        $this->dispatch('close-modal');
    }

    public function approveReservation(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'fleet.reservations', 'edit'), 403);

        $data = $this->validate([
            'review_reservation' => ['required', 'exists:reservations,reservation_number'],
            'review_notes'       => ['nullable', 'string', 'max:1000'],
            'assign_vehicle'     => ['nullable', 'exists:vehicles,id'],
            'assign_driver'      => ['nullable', 'exists:drivers,id'],
        ], [
            'review_reservation.required' => 'Please select a reservation to approve.',
            'assign_vehicle.exists'       => 'The selected vehicle no longer exists.',
            'assign_driver.exists'        => 'The selected driver no longer exists.',
        ]);

        $reservation = Reservation::where('reservation_number', $data['review_reservation'])->firstOrFail();

        $reservation->update([
            'status' => 'approved',
            'notes'  => $data['review_notes'] ?: $reservation->notes,
        ]);

        // If vehicle + driver are both assigned, create a dispatch record
        if ($data['assign_vehicle'] && $data['assign_driver']) {
            $vehicle = Vehicle::findOrFail($data['assign_vehicle']);
            $driver  = Driver::findOrFail($data['assign_driver']);

            Dispatch::create([
                'dispatch_number'    => 'DSP-' . now()->format('Y-') . str_pad(
                    Dispatch::whereYear('created_at', now()->year)->count() + 1,
                    4, '0', STR_PAD_LEFT
                ),
                'reservation_id'     => $reservation->id,
                'vehicle_id'         => $vehicle->id,
                'driver_id'          => $driver->id,
                'dispatcher_user_id' => auth()->id(),
                'assigned_at'        => now(),
                'status'             => 'assigned',
            ]);

            // Mark vehicle and driver as assigned
            $vehicle->update(['status' => 'assigned']);
            $driver->update(['availability_status' => 'assigned']);

            $reservation->update(['status' => 'assigned']);

            $this->bannerMessage = "Reservation {$reservation->reservation_number} approved and assigned to {$vehicle->plate_number} / {$driver->user?->name}.";
        } else {
            $this->bannerMessage = "Reservation {$reservation->reservation_number} approved. Assign a vehicle and driver to dispatch.";
        }

        $this->resetReviewForm();
        $this->dispatch('close-modal');
    }

    public function rejectReservation(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'fleet.reservations', 'edit'), 403);

        $data = $this->validate([
            'review_reservation' => ['required', 'exists:reservations,reservation_number'],
            'review_notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $reservation = Reservation::where('reservation_number', $data['review_reservation'])->firstOrFail();

        $reservation->update([
            'status' => 'rejected',
            'notes'  => $data['review_notes'] ?: $reservation->notes,
        ]);

        $this->bannerMessage = "Reservation {$reservation->reservation_number} has been rejected.";
        $this->resetReviewForm();
        $this->dispatch('close-modal');
    }

    private function resetForm(): void
    {
        $this->purpose          = 'Center Collection';
        $this->route            = '';
        $this->vehicle_type     = VehicleType::orderBy('name')->value('name') ?? '';
        $this->passengers       = 1;
        $this->load_level       = 'light';
        $this->start            = now()->addDay()->setTime(7, 30)->format('Y-m-d\TH:i');
        $this->end              = now()->addDay()->setTime(11, 30)->format('Y-m-d\TH:i');
        $this->notes            = '';
        $this->preferred_driver = '';
        $this->resetValidation();
    }

    private function resetReviewForm(): void
    {
        $this->review_reservation = '';
        $this->review_notes       = '';
        $this->assign_vehicle     = '';
        $this->assign_driver      = '';
        $this->resetValidation();
    }

    public function render()
    {
        $destinationOptions = $this->loadDestinationOptions();

        // Available vehicles for assignment dropdown
        $vehicleOptions = Vehicle::with(['type'])
            ->whereIn('status', ['available'])
            ->orderBy('plate_number')
            ->get()
            ->mapWithKeys(fn ($v) => [$v->id => "{$v->plate_number} ({$v->type?->name})"])
            ->all();

        // Available drivers for assignment in the REVIEW modal
        $driverOptions = Driver::with('user')
            ->where('availability_status', 'available')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->id => $d->user?->name ?? "Driver #{$d->id}"])
            ->all();

        // All drivers for selection in the NEW RESERVATION form (preferred driver)
        $driverFormOptions = Driver::with('user')
            ->whereIn('availability_status', ['available', 'assigned'])
            ->whereDate('license_expires_at', '>=', now()->toDateString())
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function ($d) {
                $status = match ($d->availability_status) {
                    'available' => '✓ Available',
                    'assigned'  => '⏳ Assigned',
                    default     => $d->availability_status,
                };
                $name = $d->user?->name ?? "Driver #{$d->id}";
                return [$d->id => "{$name} — {$status} ({$d->license_restrictions})"];
            })
            ->all();

        return view('livewire.fleet.reservations', [
            'reservations'        => Reservation::with(['requester', 'route', 'vehicleType', 'preferredDriver.user', 'dispatch.vehicle', 'dispatch.driver.user'])
                ->latest('scheduled_start_at')
                ->get(),
            'reviewOptions'       => Reservation::whereIn('status', ['pending', 'approved'])
                ->orderBy('scheduled_start_at')
                ->pluck('reservation_number', 'reservation_number')
                ->all(),
            'depotOptions'        => Depot::orderBy('name')->pluck('name', 'name')->all(),
            'destinationOptions'  => $destinationOptions,
            'vehicleTypeOptions'  => VehicleType::orderBy('name')->pluck('name', 'name')->all(),
            'vehicleOptions'      => $vehicleOptions,
            'driverOptions'       => $driverOptions,
            'driverFormOptions'   => $driverFormOptions,
            'canCreateReservation' => Rbac::allowsRoute(auth()->user()?->role, 'fleet.reservations', 'create'),
            'canReviewReservation' => Rbac::allowsRoute(auth()->user()?->role, 'fleet.reservations', 'edit'),
            'canViewPrediction'    => Rbac::allowsRoute(auth()->user()?->role, 'intelligence.ml', 'view'),
            'canAssignDispatch'    => Rbac::allowsRoute(auth()->user()?->role, 'fleet.dispatch', 'view'),
        ]);
    }
}
