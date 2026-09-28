<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Format;
use App\Support\Nav;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenContractsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'role' => 'Fleet Manager',
            'branch' => 'Malolos Main Depot',
        ]));
    }

    public function test_money_is_formatted_as_philippine_pesos(): void
    {
        $this->assertSame('₱1,234,567.89', Format::peso(1234567.89));
        $this->assertSame('₱0.00', Format::peso(0));
        $this->assertSame('₱1.23M', Format::pesoCompact(1234567.89));
    }

    public function test_delta_tone_is_decided_by_meaning_not_direction(): void
    {
        $this->assertSame('good', Format::deltaTone(3.4, 'up'));
        $this->assertSame('bad', Format::deltaTone(-3.4, 'up'));
        $this->assertSame('bad', Format::deltaTone(0.8, 'down'));
        $this->assertSame('good', Format::deltaTone(-0.8, 'down'));
    }

    public function test_dashboard_colours_transport_cost_risk_red_and_utilization_green(): void
    {
        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $fuelCard = $this->sliceAround($html, 'Today&#039;s Fuel Spend');
        $utilizationCard = $this->sliceAround($html, 'Fleet Utilization');
        $varianceCard = $this->sliceAround($html, 'ML Cost Variance');

        $this->assertStringContainsString('text-danger', $fuelCard);
        $this->assertStringContainsString('text-danger', $varianceCard);
        $this->assertStringContainsString('text-success', $utilizationCard);
    }

    private function sliceAround(string $html, string $needle): string
    {
        $start = strpos($html, $needle);
        $this->assertNotFalse($start, "Could not find [$needle] in the page.");

        $end = strpos($html, '</article>', $start);
        $this->assertNotFalse($end, "Stat card for [$needle] is not closed.");

        return substr($html, $start, $end - $start);
    }

    public function test_status_badges_include_fleet_states(): void
    {
        $this->assertSame('success', Format::badgeTone('Available'));
        $this->assertSame('warning', Format::badgeTone('Assigned'));
        $this->assertSame('info', Format::badgeTone('In Transit'));
        $this->assertSame('muted', Format::badgeTone('Under Maintenance'));
        $this->assertSame('warning', Format::badgeTone('Due Soon'));
    }

    public function test_permission_matrix_covers_only_the_six_fleet_modules_and_fleet_roles(): void
    {
        $this->assertCount(6, Nav::modules());

        $matrix = Rbac::matrix();
        $this->assertCount(15, $matrix);

        foreach ($matrix as $row) {
            $this->assertCount(4, $row);
            foreach ($row as $permissions) {
                $this->assertSame(['view', 'create', 'edit', 'delete'], array_keys($permissions));
            }
        }

        $this->assertSame(['Super Admin', 'Fleet Manager', 'Dispatcher', 'Driver'], Rbac::roles());
        $this->assertTrue(Rbac::allows('Dispatcher', 'reservation-dispatch', 'edit'));
        $this->assertTrue(Rbac::allowsRoute('Dispatcher', 'logistics.fuel', 'edit'));
        $this->assertFalse(Rbac::allowsRoute('Dispatcher', 'intelligence.ml', 'view'));
        $this->assertTrue(Rbac::allowsRoute('Driver', 'fleet.trips', 'view'));
        $this->assertTrue(Rbac::allowsRoute('Driver', 'logistics.expenses', 'create'));
        $this->assertFalse(Rbac::allowsRoute('Driver', 'fleet.dispatch', 'view'));
        $this->assertFalse(Rbac::allowsRoute('Driver', 'intelligence.costs', 'view'));
    }

    public function test_fleet_pages_expose_the_required_operational_language(): void
    {
        $this->get(route('fleet.reservations'))
            ->assertOk()
            ->assertSee('Vehicle Reservations')
            ->assertSee('Predicted Cost')
            ->assertSee('Center / Barangay');

        $this->get(route('fleet.dispatch'))
            ->assertOk()
            ->assertSee('Gate Checkout')
            ->assertSee('Starting odometer')
            ->assertSee('Return odometer');

        $this->get(route('fleet.trips'))
            ->assertOk()
            ->assertSee('Trip Ledger')
            ->assertSee('Odometer')
            ->assertSee('Cost / km');
    }

    public function test_module_screens_are_not_left_as_placeholders(): void
    {
        $routes = [
            'fleet.vehicles',
            'fleet.reservations',
            'fleet.dispatch',
            'fleet.drivers',
            'fleet.trips',
            'logistics.fuel',
            'logistics.expenses',
            'logistics.maintenance',
            'logistics.routes',
            'logistics.depots',
            'intelligence.costs',
            'intelligence.efficiency',
            'intelligence.variance',
            'intelligence.ml',
            'intelligence.reports',
        ];

        foreach ($routes as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertDontSee('under active development')
                ->assertDontSee('New Record');
        }
    }

    public function test_module_action_buttons_open_modals_or_navigate_to_integrated_screens(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create([
            'role' => 'Super Admin',
            'branch' => 'Malolos Main Depot',
        ]));

        $modalContracts = [
            'fleet.vehicles' => ['vehicle-entry', 'vehicle-detail', 'export-vehicles'],
            'fleet.reservations' => ['reservation-entry', 'review-reservation', 'export-reservations'],
            'fleet.dispatch' => ['dispatch-assignment', 'gate-checkout', 'dispatch-detail'],
            'fleet.drivers' => ['driver-entry', 'export-drivers'],
            'fleet.trips' => ['trip-checkin', 'export-trips'],
            'logistics.fuel' => ['fuel-entry', 'export-fuel'],
            'logistics.expenses' => ['expense-entry', 'approve-expense', 'export-expenses'],
            'logistics.maintenance' => ['maintenance-entry', 'close-work-order', 'export-maintenance'],
            'logistics.routes' => ['route-entry', 'export-routes'],
            'logistics.depots' => ['depot-entry', 'export-depots'],
            'intelligence.costs' => ['recalculate-cost', 'export-costs'],
            'intelligence.efficiency' => ['refresh-efficiency', 'export-efficiency'],
            'intelligence.variance' => ['reconcile-variance', 'variance-detail', 'export-variance'],
            'intelligence.ml' => ['run-prediction', 'export-ml'],
            'intelligence.reports' => ['schedule-report', 'export-report'],
        ];

        foreach ($modalContracts as $routeName => $modalNames) {
            $response = $this->get(route($routeName))->assertOk();

            foreach ($modalNames as $modalName) {
                $response->assertSee("open-modal', '{$modalName}'", false);
            }
        }

        $this->get(route('fleet.vehicles'))
            ->assertSee(route('fleet.dispatch'), false)
            ->assertSee(route('logistics.fuel'), false)
            ->assertSee(route('logistics.maintenance'), false);

        $this->get(route('fleet.reservations'))
            ->assertSee(route('fleet.dispatch'), false)
            ->assertSee(route('intelligence.ml'), false);

        $this->get(route('fleet.dispatch'))
            ->assertSee(route('fleet.trips'), false)
            ->assertSee(route('logistics.routes'), false)
            ->assertSee(route('logistics.fuel'), false);

        $this->get(route('fleet.trips'))->assertSee(route('intelligence.costs'), false);
        $this->get(route('logistics.routes'))->assertSee(route('fleet.reservations'), false);
        $this->get(route('logistics.depots'))->assertSee(route('fleet.vehicles'), false);
    }

    public function test_fleet_manager_intelligence_pages_are_read_only(): void
    {
        $this->seed();

        $hiddenWriteModals = [
            'intelligence.costs' => 'recalculate-cost',
            'intelligence.efficiency' => 'refresh-efficiency',
            'intelligence.variance' => 'reconcile-variance',
            'intelligence.ml' => 'run-prediction',
            'intelligence.reports' => 'schedule-report',
        ];

        foreach ($hiddenWriteModals as $routeName => $modalName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertDontSee("open-modal', '{$modalName}'", false);
        }
    }

    public function test_all_module_buttons_are_wired_to_a_click_handler_or_link(): void
    {
        $viewRoots = [
            resource_path('views/livewire/fleet'),
            resource_path('views/livewire/logistics'),
            resource_path('views/livewire/intelligence'),
        ];

        foreach ($viewRoots as $viewRoot) {
            foreach (glob($viewRoot.'/*.blade.php') as $viewPath) {
                preg_match_all('/<x-btn\b[^>]*>/', file_get_contents($viewPath), $matches);

                foreach ($matches[0] as $buttonTag) {
                    $isWired = str_contains($buttonTag, '@click')
                        || str_contains($buttonTag, 'wire:click')
                        || str_contains($buttonTag, ':href')
                        || str_contains($buttonTag, ' href=')
                        || str_contains($buttonTag, 'type="submit"');

                    $this->assertTrue(
                        $isWired,
                        sprintf(
                            'Button is missing an action in %s: %s',
                            str_replace(base_path().DIRECTORY_SEPARATOR, '', $viewPath),
                            $buttonTag
                        )
                    );
                }
            }
        }
    }

    public function test_topbar_has_realtime_depot_search_and_notification_controls(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('topbarRuntime', false)
            ->assertSee('x-model.debounce.150ms="searchQuery"', false)
            ->assertSee('filteredSearch', false)
            ->assertSee('switchDepot(1)', false)
            ->assertSee('Mark all read')
            ->assertSee('markAllRead()', false)
            ->assertSee('unreadCount', false);

        $this->assertStringContainsString(
            "Alpine.data('topbarRuntime'",
            file_get_contents(resource_path('js/app.js'))
        );

        $this->assertStringContainsString(
            "Alpine.data('filterBar'",
            file_get_contents(resource_path('js/app.js'))
        );
    }
}
