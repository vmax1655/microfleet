<?php

namespace Tests\Feature;

use App\Models\Dispatch;
use App\Models\FuelTransaction;
use App\Models\Inspection;
use App\Models\MaintenanceAlert;
use App\Models\Reservation;
use App\Models\TransportCost;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_fleet_domain_has_the_step_two_foundation(): void
    {
        $this->seed();

        $this->assertSame(4, Vehicle::count());
        $this->assertSame(4, TransportRoute::count());
        $this->assertSame(3, Reservation::count());
        $this->assertSame(1, Dispatch::count());
        $this->assertSame(2, Trip::count());
        $this->assertSame(1, FuelTransaction::count());
        $this->assertSame(1, TransportCost::count());
        $this->assertSame(2, Inspection::count());
        $this->assertSame(2, MaintenanceAlert::count());
    }

    public function test_in_transit_trip_has_vehicle_driver_route_and_location_points(): void
    {
        $this->seed();

        $trip = Trip::query()
            ->where('status', 'in_transit')
            ->with(['vehicle', 'driver.user', 'route', 'locations'])
            ->firstOrFail();

        $this->assertSame('MC-104', $trip->vehicle->plate_number);
        $this->assertSame('Lito M. Ramos', $trip->driver->user->name);
        $this->assertNotNull($trip->route);
        $this->assertCount(4, $trip->locations);
    }

    public function test_reservation_dispatch_trip_chain_keeps_route_vehicle_and_driver_data_integrated(): void
    {
        $this->seed();

        $reservation = Reservation::query()
            ->where('reservation_number', 'RSV-2026-0418')
            ->with(['route', 'vehicleType', 'activeDispatch.trip.route', 'activeDispatch.vehicle.type', 'activeDispatch.driver'])
            ->firstOrFail();

        $dispatch = $reservation->activeDispatch;
        $trip = $dispatch->trip;

        $this->assertSame($reservation->route_id, $trip->route_id);
        $this->assertSame($reservation->vehicle_type_id, $dispatch->vehicle->vehicle_type_id);
        $this->assertSame('in_transit', $dispatch->status);
        $this->assertSame('in_transit', $trip->status);
        $this->assertSame('in_transit', $dispatch->vehicle->status);
        $this->assertSame('in_transit', $dispatch->driver->availability_status);
    }

    public function test_completed_trip_integrates_fuel_expenses_and_transport_cost_rollups(): void
    {
        $this->seed();

        $trip = Trip::query()
            ->where('trip_number', 'TRP-2026-0180')
            ->with(['fuelTransactions', 'expenses', 'transportCost', 'vehicle.transportCosts', 'driver.transportCosts', 'route.transportCosts'])
            ->firstOrFail();

        $fuelCost = (float) $trip->fuelTransactions->sum('total_cost');
        $expenseCost = (float) $trip->expenses->sum('amount');
        $cost = $trip->transportCost;

        $this->assertSame(round($fuelCost, 2), round((float) $cost->fuel_cost, 2));
        $this->assertSame(round($expenseCost, 2), round((float) $cost->expense_cost, 2));
        $this->assertSame(round($fuelCost + $expenseCost + (float) $cost->maintenance_allocation, 2), round((float) $cost->total_cost, 2));
        $this->assertTrue($trip->vehicle->transportCosts->contains('id', $cost->id));
        $this->assertTrue($trip->driver->transportCosts->contains('id', $cost->id));
        $this->assertTrue($trip->route->transportCosts->contains('id', $cost->id));
    }

    public function test_route_planning_and_cost_modules_share_prediction_data_through_reservations(): void
    {
        $this->seed();

        $reservation = Reservation::query()
            ->where('reservation_number', 'RSV-2026-0419')
            ->with(['route', 'predictions'])
            ->firstOrFail();

        $prediction = $reservation->predictions->first();

        $this->assertNotNull($reservation->route);
        $this->assertNotNull($prediction);
        $this->assertSame((string) $reservation->predicted_fuel_liters, (string) $prediction->predicted_fuel_liters);
        $this->assertSame((string) $reservation->predicted_cost, (string) $prediction->predicted_cost);
        $this->assertSame($reservation->route->route_code, $prediction->feature_payload['route_code']);
    }

    public function test_step_three_inspection_failure_generates_maintenance_alert_and_work_order(): void
    {
        $this->seed();

        $inspection = Inspection::query()
            ->where('inspection_number', 'INS-2026-0302')
            ->with(['vehicle.maintenanceAlerts.maintenanceRecord', 'items', 'alerts.maintenanceRecord'])
            ->firstOrFail();

        $alert = $inspection->alerts->first();

        $this->assertSame('failed', $inspection->result);
        $this->assertTrue($inspection->items->contains('status', 'failed'));
        $this->assertNotNull($alert);
        $this->assertSame('critical', $alert->severity);
        $this->assertSame('PUV-118', $alert->vehicle->plate_number);
        $this->assertSame('WO-2026-0091', $alert->maintenanceRecord->work_order_number);
        $this->assertSame('under_maintenance', $inspection->vehicle->status);
    }

    public function test_fuel_logs_remain_attached_to_trip_vehicle_driver_and_cost_rollup(): void
    {
        $this->seed();

        $fuel = FuelTransaction::query()
            ->where('receipt_number', 'FUEL-2026-1001')
            ->with(['trip.transportCost', 'vehicle', 'driver.user'])
            ->firstOrFail();

        $this->assertSame('TRP-2026-0180', $fuel->trip->trip_number);
        $this->assertSame('VAN-022', $fuel->vehicle->plate_number);
        $this->assertSame('Nora C. Cruz', $fuel->driver->user->name);
        $this->assertSame(round((float) $fuel->total_cost, 2), round((float) $fuel->trip->transportCost->fuel_cost, 2));
    }
}
