<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreensSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create([
            'role' => 'Fleet Manager',
            'branch' => 'Malolos Main Depot',
            'is_active' => true,
        ]);
    }

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Fleet and transportation operations, clearly managed');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('screenProvider')]
    public function test_screen_renders(string $routeName, array $params): void
    {
        $this->actingAs($this->manager())
            ->get(route($routeName, $params))
            ->assertOk();
    }

    public static function screenProvider(): array
    {
        $screens = [
            ['dashboard', []],
            ['fleet.vehicles', []],
            ['fleet.drivers', []],
            ['fleet.reservations', []],
            ['fleet.dispatch', []],
            ['fleet.trips', []],
            ['logistics.fuel', []],
            ['logistics.expenses', []],
            ['logistics.maintenance', []],
            ['logistics.routes', []],
            ['logistics.depots', []],
            ['intelligence.costs', []],
            ['intelligence.ml', []],
            ['intelligence.variance', []],
            ['intelligence.efficiency', []],
            ['intelligence.reports', []],
        ];

        return collect($screens)
            ->mapWithKeys(fn ($screen) => [$screen[0] => $screen])
            ->all();
    }
}
