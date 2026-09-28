<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsManager(): self
    {
        $this->actingAs(User::factory()->create([
            'role' => 'Fleet Manager',
            'branch' => 'Malolos Main Depot',
        ]));

        return $this;
    }

    private function actingAsRole(string $role): self
    {
        $this->actingAs(User::factory()->create([
            'role' => $role,
            'branch' => 'Malolos Main Depot',
        ]));

        return $this;
    }

    public function test_sidebar_renders_dashboard_plus_six_fleet_modules(): void
    {
        $response = $this->actingAsManager()->get(route('dashboard'));

        $modules = Nav::modules();
        $this->assertCount(6, $modules);

        $expected = [
            'Fleet & Vehicle Management',
            'Reservation & Dispatch',
            'Driver & Trip Performance',
            'Fuel Management',
            'Transport Cost Analysis',
            'Route Planning & Optimization',
        ];

        $this->assertSame($expected, array_column($modules, 'label'));

        foreach ($modules as $module) {
            $response->assertSee('toggleModule(\''.$module['key'].'\')', false);
            $response->assertSee('aria-controls="submenu-'.$module['key'].'"', false);
            $response->assertSee($module['label']);

            foreach ($module['items'] as $item) {
                $response->assertSee($item['label']);
                $response->assertSee(route($item['route']), false);
            }
        }

        $this->assertArrayNotHasKey('items', Nav::dashboard());
    }

    public function test_module_containing_the_current_page_is_expanded_on_load(): void
    {
        $this->actingAsManager()
            ->get(route('fleet.vehicles'))
            ->assertSee("ledgerShell('fleet-vehicle')", false);

        $this->actingAsManager()
            ->get(route('fleet.dispatch'))
            ->assertSee("ledgerShell('reservation-dispatch')", false);

        $this->actingAsManager()
            ->get(route('dashboard'))
            ->assertSee("ledgerShell('')", false);
    }

    public function test_breadcrumb_follows_the_fleet_module_tree(): void
    {
        $trail = Nav::breadcrumb('logistics.routes');

        $this->assertSame('Route Planning & Optimization', $trail[0]['label']);
        $this->assertSame('Center Routes', $trail[1]['label']);

        $this->actingAsManager()
            ->get(route('logistics.routes'))
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('Route Planning &amp; Optimization', false);
    }

    public function test_collapsed_rail_exposes_a_flyout_per_module(): void
    {
        $response = $this->actingAsManager()->get(route('dashboard'));

        foreach (Nav::modules() as $module) {
            $response->assertSee("flyout === '{$module['key']}'", false);
        }

        $response->assertSee('toggleCollapse()', false);
        $response->assertSee("collapsed ? 'Expand sidebar' : 'Collapse sidebar'", false);
    }

    public function test_dispatcher_sidebar_only_shows_dispatch_and_fuel_work(): void
    {
        $response = $this->actingAsRole('Dispatcher')->get(route('dashboard'))->assertOk();

        $response->assertSee('Reservation &amp; Dispatch', false)
            ->assertSee('Vehicle Reservations')
            ->assertSee('Dispatch Board')
            ->assertSee('Fuel Management')
            ->assertSee('Fuel Transactions')
            ->assertSee('Transport Cost Analysis')
            ->assertSee('Trip Expenses')
            ->assertDontSee('Fleet &amp; Vehicle Management', false)
            ->assertDontSee('Driver &amp; Trip Performance', false)
            ->assertDontSee('ML Fuel Predictor')
            ->assertDontSee('Prediction vs Actual')
            ->assertDontSee('Route Planning &amp; Optimization', false);
    }

    public function test_driver_sidebar_only_shows_vehicle_registry_and_own_trip_monitoring(): void
    {
        $response = $this->actingAsRole('Driver')->get(route('dashboard'))->assertOk();

        $response->assertSee('Fleet &amp; Vehicle Management', false)
            ->assertSee('Vehicle Registry')
            ->assertSee('Driver &amp; Trip Performance', false)
            ->assertSee('Trip Monitoring')
            ->assertSee('Transport Cost Analysis')
            ->assertSee('Trip Expenses')
            ->assertDontSee('Driver Management')
            ->assertDontSee('Reservation &amp; Dispatch', false)
            ->assertDontSee('Fuel Management')
            ->assertDontSee('ML Fuel Predictor')
            ->assertDontSee('Prediction vs Actual')
            ->assertDontSee('Route Planning &amp; Optimization', false);
    }

    public function test_rbac_blocks_direct_module_urls_outside_the_role_scope(): void
    {
        $this->actingAsRole('Dispatcher');
        $this->get(route('fleet.reservations'))->assertOk();
        $this->get(route('fleet.dispatch'))->assertOk();
        $this->get(route('logistics.fuel'))->assertOk();
        $this->get(route('logistics.expenses'))->assertOk();
        $this->get(route('fleet.vehicles'))->assertForbidden();
        $this->get(route('intelligence.ml'))->assertForbidden();
        $this->get(route('logistics.routes'))->assertForbidden();

        $this->actingAsRole('Driver');
        $this->get(route('fleet.vehicles'))->assertOk();
        $this->get(route('fleet.trips'))->assertOk();
        $this->get(route('logistics.expenses'))->assertOk();
        $this->get(route('fleet.dispatch'))->assertForbidden();
        $this->get(route('logistics.fuel'))->assertForbidden();
        $this->get(route('intelligence.costs'))->assertForbidden();
    }
}
