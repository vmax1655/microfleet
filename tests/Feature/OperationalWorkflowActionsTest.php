<?php

namespace Tests\Feature;

use App\Livewire\Logistics\Fuel;
use App\Livewire\Logistics\Expenses;
use App\Livewire\Logistics\Maintenance;
use App\Livewire\Fleet\Drivers;
use App\Livewire\Fleet\Dispatch;
use App\Livewire\Fleet\Reservations;
use App\Livewire\Fleet\Trips;
use App\Livewire\Fleet\Vehicles;
use App\Models\Depot;
use App\Models\Dispatch as DispatchModel;
use App\Models\Driver;
use App\Models\FuelTransaction;
use App\Models\Inspection;
use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\Reservation;
use App\Models\TransportCost;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalWorkflowActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->actingAs(User::factory()->create([
            'role' => 'Fleet Manager',
            'branch' => 'Malolos Main Depot',
            'is_active' => true,
        ]));
    }

    public function test_fuel_modal_action_creates_fuel_transaction_and_updates_odometer(): void
    {
        Livewire::test(Fuel::class)
            ->set('vehicle', 'VAN-022')
            ->set('trip', 'TRP-2026-0180')
            ->set('station', 'Caltex Malolos')
            ->set('receipt', 'FUEL-TEST-0001')
            ->set('liters', '12.50')
            ->set('unit_price', '64.00')
            ->set('odometer', '63050')
            ->set('fueled_at', '2026-09-23')
            ->call('saveFuel')
            ->assertHasNoErrors();

        $fuel = FuelTransaction::where('receipt_number', 'FUEL-TEST-0001')->firstOrFail();

        $this->assertSame(800.00, round((float) $fuel->total_cost, 2));
        $this->assertSame('TRP-2026-0180', $fuel->trip->trip_number);
        $this->assertSame('63050.00', Vehicle::where('plate_number', 'VAN-022')->firstOrFail()->current_odometer_km);
    }

    public function test_vehicle_modal_action_creates_dispatchable_vehicle(): void
    {
        Livewire::test(Vehicles::class)
            ->set('plate', 'NEW-777')
            ->set('type', 'Motorcycle')
            ->set('depot', 'Malolos Main Depot')
            ->set('fuel', 'gasoline')
            ->set('odometer', '100')
            ->set('status', 'available')
            ->call('saveVehicle')
            ->assertHasNoErrors();

        $vehicle = Vehicle::where('plate_number', 'NEW-777')->firstOrFail();

        $this->assertSame('available', $vehicle->status);
        $this->assertSame('Motorcycle', $vehicle->type->name);
    }

    public function test_driver_modal_action_creates_driver_user_and_profile(): void
    {
        Livewire::test(Drivers::class)
            ->set('name', 'Test Driver')
            ->set('employee', 'DRV-777')
            ->set('license', 'LIC-DRV-777')
            ->set('restrictions', 'N01/N02')
            ->set('expires', now()->addYear()->toDateString())
            ->set('status', 'available')
            ->call('saveDriver')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'drv-777@microfleet.local', 'role' => 'Driver']);
        $this->assertDatabaseHas('drivers', ['employee_number' => 'DRV-777', 'availability_status' => 'available']);
    }

    public function test_reservation_modal_action_creates_prediction_backed_reservation(): void
    {
        Livewire::test(Reservations::class)
            ->set('purpose', 'Center Collection')
            ->set('route', 'RTE-HG-08')
            ->set('vehicle_type', 'Motorcycle')
            ->set('passengers', 1)
            ->set('load_level', 'light')
            ->set('start', now()->addDay()->setTime(8, 0)->format('Y-m-d\TH:i'))
            ->set('end', now()->addDay()->setTime(12, 0)->format('Y-m-d\TH:i'))
            ->set('notes', 'Field collection route.')
            ->call('saveReservation')
            ->assertHasNoErrors();

        $reservation = Reservation::where('notes', 'Field collection route.')->firstOrFail();

        $this->assertSame('pending', $reservation->status);
        $this->assertGreaterThan(0, (float) $reservation->predicted_cost);
        $this->assertTrue($reservation->predictions()->exists());
    }

    public function test_reservation_review_actions_approve_and_reject_requests(): void
    {
        Livewire::test(Reservations::class)
            ->set('review_reservation', 'RSV-2026-0420')
            ->set('review_notes', 'Approved for field verification.')
            ->call('approveReservation')
            ->assertHasNoErrors();

        $this->assertSame('approved', Reservation::where('reservation_number', 'RSV-2026-0420')->firstOrFail()->status);

        $reservation = Reservation::create([
            'reservation_number' => 'RSV-TEST-REJECT',
            'requester_user_id' => auth()->id(),
            'route_id' => TransportRoute::where('route_code', 'RTE-HG-08')->value('id'),
            'vehicle_type_id' => VehicleType::where('name', 'Motorcycle')->value('id'),
            'purpose' => 'KYC Verification',
            'passenger_count' => 1,
            'load_level' => 'light',
            'scheduled_start_at' => now()->addDay(),
            'scheduled_end_at' => now()->addDay()->addHours(2),
            'status' => 'pending',
        ]);

        Livewire::test(Reservations::class)
            ->set('review_reservation', $reservation->reservation_number)
            ->set('review_notes', 'Route window needs correction.')
            ->call('rejectReservation')
            ->assertHasNoErrors();

        $this->assertSame('rejected', $reservation->fresh()->status);
    }

    public function test_dispatch_assignment_links_approved_reservation_vehicle_and_driver(): void
    {
        $type = VehicleType::where('name', 'Passenger Van')->firstOrFail();
        $depot = Depot::where('name', 'Malolos Main Depot')->firstOrFail();
        $driverUser = User::factory()->create(['role' => 'Driver', 'is_active' => true]);

        Vehicle::create([
            'depot_id' => $depot->id,
            'vehicle_type_id' => $type->id,
            'plate_number' => 'VAN-TST',
            'fuel_type' => 'diesel',
            'current_odometer_km' => 12500,
            'status' => 'available',
        ]);

        Driver::create([
            'user_id' => $driverUser->id,
            'employee_number' => 'DRV-TST',
            'license_number' => 'LIC-DRV-TST',
            'license_expires_at' => now()->addYear()->toDateString(),
            'availability_status' => 'available',
        ]);

        $reservation = Reservation::create([
            'reservation_number' => 'RSV-TEST-ASSIGN',
            'requester_user_id' => auth()->id(),
            'route_id' => TransportRoute::where('route_code', 'RTE-HG-08')->value('id'),
            'vehicle_type_id' => $type->id,
            'purpose' => 'Branch Audit',
            'passenger_count' => 4,
            'load_level' => 'medium',
            'scheduled_start_at' => now()->addDay(),
            'scheduled_end_at' => now()->addDay()->addHours(4),
            'status' => 'approved',
        ]);

        Livewire::test(Dispatch::class)
            ->set('reservation', $reservation->reservation_number)
            ->set('vehicle', 'VAN-TST')
            ->set('driver', 'DRV-TST')
            ->set('assigned_at', now()->format('Y-m-d\TH:i'))
            ->call('assignDispatch')
            ->assertHasNoErrors();

        $dispatch = DispatchModel::where('reservation_id', $reservation->id)->firstOrFail();

        $this->assertSame('assigned', $dispatch->status);
        $this->assertSame('assigned', $reservation->fresh()->status);
        $this->assertSame('assigned', Vehicle::where('plate_number', 'VAN-TST')->firstOrFail()->status);
        $this->assertSame('assigned', Driver::where('employee_number', 'DRV-TST')->firstOrFail()->availability_status);
    }

    public function test_failed_inspection_creates_alert_and_holds_vehicle_from_dispatch(): void
    {
        Livewire::test(Maintenance::class)
            ->set('inspection_vehicle', 'VAN-022')
            ->set('inspection_type', 'Yard check')
            ->set('inspection_odometer', '63055')
            ->set('inspection_result', 'Failed')
            ->set('inspection_notes', 'Brake fluid leak found during yard check.')
            ->call('saveInspection')
            ->assertHasNoErrors();

        $inspection = Inspection::where('vehicle_id', Vehicle::where('plate_number', 'VAN-022')->value('id'))
            ->latest()
            ->firstOrFail();

        $this->assertSame('failed', $inspection->result);
        $this->assertTrue($inspection->items()->where('status', 'failed')->exists());
        $this->assertTrue(MaintenanceAlert::where('inspection_id', $inspection->id)->where('severity', 'critical')->exists());
        $this->assertSame('under_maintenance', $inspection->vehicle->fresh()->status);
    }

    public function test_work_order_create_and_close_updates_vehicle_availability(): void
    {
        Livewire::test(Maintenance::class)
            ->set('vehicle', 'VAN-022')
            ->set('service_type', 'Corrective')
            ->set('odometer', '63060')
            ->set('cost', '3500')
            ->set('description', 'Replace brake hose.')
            ->call('createWorkOrder')
            ->assertHasNoErrors();

        $workOrder = MaintenanceRecord::where('vehicle_id', Vehicle::where('plate_number', 'VAN-022')->value('id'))
            ->latest('opened_at')
            ->firstOrFail();

        $this->assertSame('open', $workOrder->status);
        $this->assertSame('under_maintenance', $workOrder->vehicle->fresh()->status);

        Livewire::test(Maintenance::class)
            ->set('selected_work_order', $workOrder->work_order_number)
            ->set('completion_notes', 'Repair completed and road test passed.')
            ->call('closeWorkOrder')
            ->assertHasNoErrors();

        $this->assertSame('completed', $workOrder->fresh()->status);
        $this->assertSame('available', $workOrder->vehicle->fresh()->status);
    }

    public function test_close_work_order_targets_the_selected_work_order_not_the_latest_open_one(): void
    {
        $vehicle = Vehicle::where('plate_number', 'VAN-022')->firstOrFail();

        $older = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'work_order_number' => 'WO-TEST-OLDER',
            'service_type' => 'corrective',
            'description' => 'Older selected work order.',
            'odometer_km' => 63000,
            'parts_cost' => 100,
            'labor_cost' => 50,
            'total_cost' => 150,
            'status' => 'open',
            'opened_at' => now()->subHours(2),
        ]);

        $latest = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'work_order_number' => 'WO-TEST-LATEST',
            'service_type' => 'preventive',
            'description' => 'Latest open work order.',
            'odometer_km' => 63100,
            'parts_cost' => 200,
            'labor_cost' => 100,
            'total_cost' => 300,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        Livewire::test(Maintenance::class)
            ->call('selectWorkOrder', $older->work_order_number)
            ->assertSet('selected_work_order', $older->work_order_number)
            ->set('completion_notes', 'Closed selected older work order.')
            ->call('closeWorkOrder')
            ->assertHasNoErrors();

        $this->assertSame('completed', $older->fresh()->status);
        $this->assertSame('open', $latest->fresh()->status);
    }

    public function test_gate_checkout_starts_dispatch_and_creates_trip(): void
    {
        Livewire::test(Dispatch::class)
            ->set('dispatch', 'DSP-2026-0181')
            ->set('odometer', '18125')
            ->set('condition', 'Passed')
            ->set('time', now()->format('Y-m-d\TH:i'))
            ->call('gateCheckout')
            ->assertHasNoErrors();

        $trip = Trip::whereHas('dispatch', fn ($query) => $query->where('dispatch_number', 'DSP-2026-0181'))->firstOrFail();

        $this->assertSame('in_transit', $trip->status);
        $this->assertSame('18125.00', $trip->start_odometer_km);
        $this->assertSame('in_transit', $trip->vehicle->status);
        $this->assertSame('in_transit', $trip->driver->availability_status);
    }

    public function test_trip_checkin_completes_trip_and_posts_cost_rollup(): void
    {
        Livewire::test(Trips::class)
            ->set('trip', 'TRP-2026-0181')
            ->set('end_odometer', '18162')
            ->set('returned_at', now()->format('Y-m-d\TH:i'))
            ->set('condition', 'Passed')
            ->set('notes', 'Trip completed normally.')
            ->call('checkInTrip')
            ->assertHasNoErrors();

        $trip = Trip::where('trip_number', 'TRP-2026-0181')->firstOrFail();

        $this->assertSame('completed', $trip->status);
        $this->assertSame('completed', $trip->dispatch?->reservation?->status);
        $this->assertSame('available', $trip->vehicle->status);
        $this->assertSame('available', $trip->driver->availability_status);
        $this->assertTrue(TransportCost::where('trip_id', $trip->id)->where('status', 'posted')->exists());
    }

    public function test_driver_dispatcher_manager_trip_expense_workflow_posts_only_approved_costs(): void
    {
        $driver = User::where('email', 'driver@microfleet.local')->firstOrFail();
        $dispatcher = User::where('email', 'dispatcher@microfleet.local')->firstOrFail();
        $manager = User::where('email', 'manager@microfleet.local')->firstOrFail();

        $this->actingAs($driver);

        Livewire::test(Expenses::class)
            ->set('trip', 'TRP-2026-0181')
            ->set('expense_type', 'Toll')
            ->set('receipt_number', 'EXP-WORKFLOW-001')
            ->set('amount', '345.50')
            ->set('incurred_at', '2026-09-23')
            ->set('description', 'Expressway toll during assigned trip.')
            ->call('submitExpense')
            ->assertHasNoErrors();

        $expense = TripExpense::where('receipt_number', 'EXP-WORKFLOW-001')->firstOrFail();
        $trip = Trip::where('trip_number', 'TRP-2026-0181')->firstOrFail();

        $this->assertSame('pending', $expense->approval_status);
        $this->assertFalse(TransportCost::where('trip_id', $trip->id)->exists());

        $this->actingAs($dispatcher);

        Livewire::test(Expenses::class)
            ->call('markChecked', $expense->id)
            ->assertHasNoErrors();

        $this->assertSame('checked', $expense->fresh()->approval_status);
        $this->assertFalse(TransportCost::where('trip_id', $trip->id)->exists());

        $this->actingAs($manager);

        Livewire::test(Expenses::class)
            ->call('selectExpense', $expense->id)
            ->call('approveExpense')
            ->assertHasNoErrors();

        $expense = $expense->fresh();
        $cost = TransportCost::where('trip_id', $trip->id)->firstOrFail();

        $this->assertSame('approved', $expense->approval_status);
        $this->assertSame($manager->id, $expense->approved_by_user_id);
        $this->assertSame(345.50, round((float) $cost->expense_cost, 2));
        $this->assertSame(345.50, round((float) $cost->total_cost, 2));
    }

    public function test_module_write_actions_are_blocked_for_roles_outside_the_workflow(): void
    {
        $driver = User::where('email', 'driver@microfleet.local')->firstOrFail();
        $dispatcher = User::where('email', 'dispatcher@microfleet.local')->firstOrFail();
        $manager = User::where('email', 'manager@microfleet.local')->firstOrFail();

        $this->actingAs($driver);

        Livewire::test(Dispatch::class)
            ->call('assignDispatch')
            ->assertForbidden();

        Livewire::test(Fuel::class)
            ->call('saveFuel')
            ->assertForbidden();

        Livewire::test(Maintenance::class)
            ->call('createWorkOrder')
            ->assertForbidden();

        $this->actingAs($dispatcher);

        Livewire::test(Drivers::class)
            ->call('saveDriver')
            ->assertForbidden();

        Livewire::test(Maintenance::class)
            ->call('saveInspection')
            ->assertForbidden();

        $this->actingAs($manager);

        Livewire::test(Expenses::class)
            ->set('trip', 'TRP-2026-0181')
            ->set('expense_type', 'Toll')
            ->set('receipt_number', 'EXP-MANAGER-BLOCKED')
            ->set('amount', '100')
            ->set('incurred_at', '2026-09-23')
            ->call('submitExpense')
            ->assertForbidden();
    }
}
