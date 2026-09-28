<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CapstoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'role' => 'Fleet Manager',
            'branch' => 'Malolos Main Depot',
            'is_active' => true,
        ]));
    }

    public function test_every_sidebar_route_has_a_registered_route_and_smoke_test_entry(): void
    {
        $declaredRoutes = collect(Nav::modules())
            ->flatMap(fn ($module) => collect($module['items'])->pluck('route'))
            ->prepend(Nav::dashboard()['route'])
            ->values();

        $smokeRoutes = collect(ScreensSmokeTest::screenProvider())
            ->map(fn ($screen) => $screen[0])
            ->values();

        foreach ($declaredRoutes as $routeName) {
            $this->assertTrue(Route::has($routeName), "Route [{$routeName}] is declared in navigation but missing from web routes.");
            $this->assertTrue($smokeRoutes->contains($routeName), "Route [{$routeName}] is missing from screen smoke coverage.");
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_step_three_to_five_tables_exist_after_migrations(): void
    {
        foreach ([
            'inspections',
            'inspection_items',
            'maintenance_alerts',
            'ml_feedback',
            'ml_predictions',
            'transport_costs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected migrated table [{$table}] to exist.");
        }
    }

    public function test_legacy_non_fleet_route_names_are_not_registered(): void
    {
        foreach (['membership.directory', 'loans.accounts', 'collections.ledger', 'savings.accounts', 'admin.users'] as $routeName) {
            $this->assertFalse(Route::has($routeName), "Legacy route [{$routeName}] should not be exposed in Microfleet.");
        }
    }
}
