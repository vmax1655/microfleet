<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\Dispatch;
use App\Models\Driver;
use App\Models\FuelTransaction;
use App\Models\Inspection;
use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\MlPrediction;
use App\Models\Reservation;
use App\Models\TransportCost;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\TripLocation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::where('email', 'like', '%@ledger.coop.ph')->delete();

        $users = $this->seedUsers();
        $depots = $this->seedDepots();
        $types = $this->seedVehicleTypes();
        $vehicles = $this->seedVehicles($depots, $types);
        $drivers = $this->seedDrivers($users);
        $routes = $this->seedRoutes($depots);
        $reservations = $this->seedReservations($users, $types, $routes);

        $this->seedDocuments($vehicles);
        $this->seedOperations($users, $vehicles, $drivers, $routes, $reservations);
    }

    private function seedUsers(): array
    {
        User::whereIn('email', [
            'management@microfleet.local',
            'finance@microfleet.local',
            'auditor@microfleet.local',
        ])->delete();

        $rows = [
            ['Teresita G. Gonzales',  'manager@microfleet.local',        'Fleet Manager', 'Malolos Main Depot'],
            ['Editha C. Ramirez',     'admin@microfleet.local',          'Super Admin',   'All Depots'],
            ['Ramon B. Aguilar',      'dispatcher@microfleet.local',     'Dispatcher',    'Malolos Main Depot'],
            ['Lito M. Ramos',         'driver@microfleet.local',         'Driver',        'Malolos Main Depot'],
            ['Nora C. Cruz',          'nora.cruz@microfleet.local',      'Driver',        'Malolos Main Depot'],
            ['Rafael P. Santos',      'rafael.santos@microfleet.local',  'Driver',        'Malolos Main Depot'],
        ];

        return collect($rows)->mapWithKeys(function ($row) {
            [$name, $email, $role, $branch] = $row;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'role' => $role,
                    'branch' => $branch,
                    'is_active' => true,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            return [$email => $user];
        })->all();
    }

    private function seedDepots(): array
    {
        $rows = [
            ['Malolos Main Depot', 'Malolos, Bulacan', 14.8527390, 120.8160380],
            ['Paombong Satellite Yard', 'Paombong, Bulacan', 14.8315130, 120.7897030],
            ['Calumpit Field Yard', 'Calumpit, Bulacan', 14.9168650, 120.7659820],
        ];

        return collect($rows)->mapWithKeys(function ($row) {
            [$name, $address, $lat, $lng] = $row;

            $depot = Depot::updateOrCreate(
                ['name' => $name],
                [
                    'address' => $address,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'status' => 'active',
                ]
            );

            return [$name => $depot];
        })->all();
    }

    private function seedVehicleTypes(): array
    {
        $rows = [
            ['Motorcycle', 'Barangay center visits and light document transport', 38.00],
            ['Multi-cab', 'Small group field work and supply transport', 14.00],
            ['Passenger Van', 'Team trips, audits, and cash delivery escort', 10.00],
            ['Utility Pickup', 'Rough route and maintenance support trips', 9.50],
        ];

        return collect($rows)->mapWithKeys(function ($row) {
            [$name, $description, $kml] = $row;

            $type = VehicleType::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'default_fuel_efficiency_kml' => $kml,
                ]
            );

            return [$name => $type];
        })->all();
    }

    private function seedVehicles(array $depots, array $types): array
    {
        $rows = [
            ['MC-104', 'Motorcycle', 'Malolos Main Depot', 'Honda', 'TMX 125', 2022, 'gasoline', 18_420, 'in_transit', 'vehicles/default-motorcycle.webp'],
            ['MCB-204', 'Multi-cab', 'Malolos Main Depot', 'Suzuki', 'Carry', 2020, 'gasoline', 42_188, 'assigned', 'vehicles/default-multi-cab.webp'],
            ['VAN-022', 'Passenger Van', 'Malolos Main Depot', 'Toyota', 'Hiace', 2019, 'diesel', 63_014, 'available', 'vehicles/default-passenger-van.jpg'],
            ['PUV-118', 'Utility Pickup', 'Paombong Satellite Yard', 'Mitsubishi', 'Strada', 2018, 'diesel', 75_880, 'under_maintenance', 'vehicles/default-utility-pickup.jpg'],
        ];

        return collect($rows)->mapWithKeys(function ($row) use ($depots, $types) {
            [$plate, $type, $depot, $make, $model, $year, $fuel, $odo, $status, $image] = $row;

            $vehicle = Vehicle::updateOrCreate(
                ['plate_number' => $plate],
                [
                    'depot_id' => $depots[$depot]->id,
                    'vehicle_type_id' => $types[$type]->id,
                    'image_path' => $image,
                    'make' => $make,
                    'model' => $model,
                    'year' => $year,
                    'fuel_type' => $fuel,
                    'tank_capacity_liters' => $type === 'Motorcycle' ? 8 : 55,
                    'current_odometer_km' => $odo,
                    'status' => $status,
                    'acquisition_date' => now()->subYears(3)->toDateString(),
                ]
            );

            return [$plate => $vehicle];
        })->all();
    }

    private function seedDrivers(array $users): array
    {
        $rows = [
            ['driver@microfleet.local', 'DRV-001', 'Code A (Motorcycle Only)', '2028-02-12', 'in_transit', 92],
            ['nora.cruz@microfleet.local', 'DRV-002', 'Code A, B, B1 (Van & Multi-cab)', '2027-06-04', 'available', 98],
            ['rafael.santos@microfleet.local', 'DRV-003', 'Code A, B, B1, B2 (Full Fleet Qualification)', '2026-09-30', 'assigned', 96],
        ];

        return collect($rows)->mapWithKeys(function ($row) use ($users) {
            [$email, $employee, $restrictions, $expires, $status, $score] = $row;
            $user = $users[$email];

            $driver = Driver::updateOrCreate(
                ['employee_number' => $employee],
                [
                    'user_id' => $user->id,
                    'license_number' => 'LIC-'.$employee,
                    'license_restrictions' => $restrictions,
                    'license_expires_at' => $expires,
                    'medical_clearance_expires_at' => now()->addYear()->toDateString(),
                    'phone' => '09'.random_int(10_000_000, 99_999_999),
                    'availability_status' => $status,
                    'safety_score' => $score,
                ]
            );

            return [$employee => $driver];
        })->all();
    }

    private function seedRoutes(array $depots): array
    {
        $rows = [
            // [code, name, center, destination, lat, lng, distance, duration, profile, origin_depot_key]
            ['RTE-SM-04', 'Malolos - San Miguel Center 04',         'CTR-04', 'San Miguel Center 04', 14.9582210, 120.9788270, 31.4, 70, 'mixed',  'Malolos Main Depot'],
            ['RTE-PB-02', 'Paombong Satellite - Paombong Center 02','CTR-02', 'Paombong Center 02',   14.8315130, 120.7897030, 10.8, 28, 'urban',  'Paombong Satellite Yard'],
            ['RTE-CP-11', 'Calumpit Field - Calumpit Center 11',    'CTR-11', 'Calumpit Center 11',   14.9168650, 120.7659820, 18.6, 44, 'mixed',  'Calumpit Field Yard'],
            ['RTE-HG-08', 'Malolos - Hagonoy Center 08',           'CTR-08', 'Hagonoy Center 08',    14.8341150, 120.7335210, 24.2, 58, 'rural',  'Malolos Main Depot'],
        ];

        return collect($rows)->mapWithKeys(function ($row) use ($depots) {
            [$code, $name, $center, $destination, $lat, $lng, $distance, $duration, $profile, $depotKey] = $row;

            $route = TransportRoute::updateOrCreate(
                ['route_code' => $code],
                [
                    'origin_depot_id'             => $depots[$depotKey]->id,
                    'name'                        => $name,
                    'center_code'                 => $center,
                    'destination_name'            => $destination,
                    'destination_latitude'        => $lat,
                    'destination_longitude'       => $lng,
                    'planned_distance_km'         => $distance,
                    'estimated_duration_minutes'  => $duration,
                    'road_profile'                => $profile,
                    'status'                      => 'active',
                ]
            );

            return [$code => $route];
        })->all();
    }


    private function seedReservations(array $users, array $types, array $routes): array
    {
        $rows = [
            ['RSV-2026-0418', 'manager@microfleet.local', 'RTE-SM-04', 'Motorcycle', 'Center Collection', 1, 'light', 'approved', 1.35, 620],
            ['RSV-2026-0419', 'manager@microfleet.local', 'RTE-PB-02', 'Multi-cab', 'Loan Release', 4, 'medium', 'assigned', 2.10, 1180],
            ['RSV-2026-0420', 'dispatcher@microfleet.local', 'RTE-CP-11', 'Motorcycle', 'KYC Verification', 1, 'light', 'pending', 1.10, 540],
        ];

        return collect($rows)->mapWithKeys(function ($row) use ($users, $types, $routes) {
            [$number, $email, $routeCode, $type, $purpose, $passengers, $load, $status, $fuel, $cost] = $row;

            $reservation = Reservation::updateOrCreate(
                ['reservation_number' => $number],
                [
                    'requester_user_id' => $users[$email]->id,
                    'route_id' => $routes[$routeCode]->id,
                    'vehicle_type_id' => $types[$type]->id,
                    'purpose' => $purpose,
                    'passenger_count' => $passengers,
                    'load_level' => $load,
                    'scheduled_start_at' => now()->setTime(7, 30)->addHours(count($row) % 3),
                    'scheduled_end_at' => now()->setTime(12, 0)->addHours(count($row) % 3),
                    'predicted_fuel_liters' => $fuel,
                    'predicted_cost' => $cost,
                    'status' => $status,
                ]
            );

            MlPrediction::updateOrCreate(
                ['reservation_id' => $reservation->id, 'model_name' => 'fuel_random_forest'],
                [
                    'model_version' => 'v1',
                    'predicted_fuel_liters' => $fuel,
                    'predicted_cost' => $cost,
                    'feature_payload' => ['route_code' => $routeCode, 'load_level' => $load],
                    'generated_at' => now(),
                ]
            );

            return [$number => $reservation];
        })->all();
    }

    private function seedDocuments(array $vehicles): void
    {
        foreach ($vehicles as $plate => $vehicle) {
            VehicleDocument::updateOrCreate(
                ['vehicle_id' => $vehicle->id, 'document_type' => 'Registration'],
                [
                    'document_number' => 'REG-'.$plate,
                    'issued_at' => now()->subMonths(8)->toDateString(),
                    'expires_at' => $plate === 'VAN-022' ? now()->addDays(7)->toDateString() : now()->addMonths(5)->toDateString(),
                    'status' => $plate === 'VAN-022' ? 'due_soon' : 'valid',
                ]
            );
        }
    }

    private function seedOperations(array $users, array $vehicles, array $drivers, array $routes, array $reservations): void
    {
        $dispatch = Dispatch::updateOrCreate(
            ['dispatch_number' => 'DSP-2026-0181'],
            [
                'reservation_id' => $reservations['RSV-2026-0418']->id,
                'vehicle_id' => $vehicles['MC-104']->id,
                'driver_id' => $drivers['DRV-001']->id,
                'dispatcher_user_id' => $users['dispatcher@microfleet.local']->id,
                'assigned_at' => now()->subHours(3),
                'checked_out_at' => now()->subHours(2),
                'start_odometer_km' => 18112,
                'checkout_condition' => 'passed',
                'status' => 'in_transit',
            ]
        );

        $trip = Trip::updateOrCreate(
            ['trip_number' => 'TRP-2026-0181'],
            [
                'dispatch_id' => $dispatch->id,
                'vehicle_id' => $vehicles['MC-104']->id,
                'driver_id' => $drivers['DRV-001']->id,
                'route_id' => $reservations['RSV-2026-0418']->route_id,
                'departed_at' => now()->subHours(2),
                'start_odometer_km' => 18112,
                'status' => 'in_transit',
            ]
        );

        $trip->locations()->delete();

        foreach ([[14.8527390, 120.8160380, 0], [14.8461020, 120.7974020, 32], [14.8391550, 120.7741200, 38], [14.8341150, 120.7335210, 24]] as $i => $point) {
            TripLocation::create([
                'trip_id' => $trip->id,
                'latitude' => $point[0],
                'longitude' => $point[1],
                'speed_kph' => $point[2],
                'heading' => 240,
                'recorded_at' => now()->subMinutes(20 - ($i * 5)),
            ]);
        }

        $completedTrip = Trip::updateOrCreate(
            ['trip_number' => 'TRP-2026-0180'],
            [
                'vehicle_id' => $vehicles['VAN-022']->id,
                'driver_id' => $drivers['DRV-002']->id,
                'route_id' => $routes['RTE-PB-02']->id,
                'departed_at' => now()->subHours(5),
                'arrived_at' => now()->subHours(2),
                'start_odometer_km' => 62880,
                'end_odometer_km' => 63014,
                'distance_km' => 134,
                'status' => 'completed',
            ]
        );

        $passedInspection = Inspection::updateOrCreate(
            ['inspection_number' => 'INS-2026-0301'],
            [
                'trip_id' => $trip->id,
                'vehicle_id' => $vehicles['MC-104']->id,
                'driver_id' => $drivers['DRV-001']->id,
                'inspection_type' => 'pre_trip',
                'result' => 'passed',
                'odometer_km' => 18112,
                'notes' => 'Lights, tires, brakes, and documents verified before dispatch.',
                'inspected_at' => now()->subHours(2)->subMinutes(10),
            ]
        );

        $this->syncInspectionItems($passedInspection, [
            ['Lights and signals', 'passed', 'Headlight, brake light, and indicators working'],
            ['Tires and brakes', 'passed', 'Tread and brake response acceptable'],
            ['Vehicle documents', 'passed', 'Registration and insurance copies onboard'],
            ['Emergency kit', 'passed', 'First aid kit and tools complete'],
        ]);

        $failedInspection = Inspection::updateOrCreate(
            ['inspection_number' => 'INS-2026-0302'],
            [
                'vehicle_id' => $vehicles['PUV-118']->id,
                'driver_id' => $drivers['DRV-003']->id,
                'inspection_type' => 'yard_check',
                'result' => 'failed',
                'odometer_km' => 75880,
                'notes' => 'Brake pedal soft during yard inspection. Vehicle held from dispatch.',
                'inspected_at' => now()->subDay()->setTime(16, 30),
            ]
        );

        $this->syncInspectionItems($failedInspection, [
            ['Lights and signals', 'passed', 'All lights working'],
            ['Tires and brakes', 'failed', 'Brake response below safety threshold'],
            ['Fluid levels', 'attention', 'Brake fluid near minimum mark'],
            ['Emergency kit', 'passed', 'Kit complete'],
        ]);

        FuelTransaction::updateOrCreate(
            ['receipt_number' => 'FUEL-2026-1001'],
            [
                'vehicle_id' => $vehicles['VAN-022']->id,
                'driver_id' => $drivers['DRV-002']->id,
                'trip_id' => $completedTrip->id,
                'station_name' => 'Petron Malolos',
                'fuel_type' => 'diesel',
                'liters' => 42.50,
                'unit_price' => 62.10,
                'total_cost' => 2639.25,
                'odometer_km' => 63014,
                'fueled_at' => now()->subHours(2),
                'status' => 'posted',
            ]
        );

        TripExpense::updateOrCreate(
            ['trip_id' => $completedTrip->id, 'expense_type' => 'parking'],
            [
                'amount' => 120,
                'receipt_number' => 'EXP-2026-0881',
                'description' => 'Municipal parking fee',
                'approval_status' => 'approved',
                'approved_by_user_id' => $users['manager@microfleet.local']->id,
                'incurred_at' => now()->subHours(3),
            ]
        );

        TransportCost::updateOrCreate(
            ['trip_id' => $completedTrip->id],
            [
                'fuel_cost' => 2639.25,
                'expense_cost' => 120,
                'maintenance_allocation' => 100.75,
                'total_cost' => 2860,
                'cost_per_km' => 21.34,
                'center_code' => 'CTR-02',
                'status' => 'posted',
            ]
        );

        MlPrediction::updateOrCreate(
            ['trip_id' => $completedTrip->id, 'model_name' => 'rule_based_fuel_cost'],
            [
                'model_version' => 'v1',
                'predicted_fuel_liters' => 38.20,
                'predicted_cost' => 2485.00,
                'actual_fuel_liters' => 42.50,
                'actual_cost' => 2860.00,
                'variance_percent' => 11.26,
                'feature_payload' => [
                    'route_code' => 'RTE-PB-02',
                    'distance_km' => 134,
                    'vehicle' => 'VAN-022',
                ],
                'generated_at' => now()->subHours(2),
            ]
        );

        $workOrder = MaintenanceRecord::updateOrCreate(
            ['work_order_number' => 'WO-2026-0091'],
            [
                'vehicle_id' => $vehicles['PUV-118']->id,
                'service_type' => 'preventive',
                'description' => '5,000 km preventive service',
                'odometer_km' => 75880,
                'parts_cost' => 1850,
                'labor_cost' => 1200,
                'total_cost' => 3050,
                'status' => 'open',
                'opened_at' => now()->subDay(),
            ]
        );

        MaintenanceAlert::updateOrCreate(
            ['alert_number' => 'MAL-2026-0144'],
            [
                'vehicle_id' => $vehicles['PUV-118']->id,
                'inspection_id' => $failedInspection->id,
                'maintenance_record_id' => $workOrder->id,
                'source_type' => 'inspection_failure',
                'severity' => 'critical',
                'title' => 'Brake system requires service',
                'description' => 'Failed yard inspection generated a critical work order and removed PUV-118 from dispatch eligibility.',
                'status' => 'open',
                'triggered_at' => now()->subDay()->setTime(16, 35),
            ]
        );

        MaintenanceAlert::updateOrCreate(
            ['alert_number' => 'MAL-2026-0145'],
            [
                'vehicle_id' => $vehicles['VAN-022']->id,
                'source_type' => 'mileage_threshold',
                'severity' => 'medium',
                'title' => 'Preventive service due soon',
                'description' => 'VAN-022 is within 250 km of the next preventive maintenance interval.',
                'status' => 'monitoring',
                'triggered_at' => now()->subHours(6),
            ]
        );
    }

    private function syncInspectionItems(Inspection $inspection, array $items): void
    {
        $inspection->items()->delete();

        foreach ($items as $item) {
            [$name, $status, $remarks] = $item;

            $inspection->items()->create([
                'item_name' => $name,
                'status' => $status,
                'remarks' => $remarks,
            ]);
        }
    }
}
