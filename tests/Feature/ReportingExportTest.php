<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingExportTest extends TestCase
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

    public function test_reports_center_links_live_csv_exports(): void
    {
        $this->get(route('intelligence.reports'))
            ->assertOk()
            ->assertSee(route('intelligence.reports.export', 'operations'), false)
            ->assertSee(route('intelligence.reports.export', 'fuel'), false)
            ->assertSee(route('intelligence.reports.export', 'costs'), false)
            ->assertSee(route('intelligence.reports.export', 'maintenance'), false)
            ->assertSee(route('intelligence.reports.export', 'compliance'), false);
    }

    public function test_cost_report_export_downloads_csv_with_integrated_trip_costs(): void
    {
        $response = $this->get(route('intelligence.reports.export', 'costs'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('Trip,Center,Fuel,Expenses,Maintenance,Total', $content);
        $this->assertStringContainsString('TRP-2026-0180,CTR-02', $content);
    }

    public function test_compliance_export_includes_vehicle_documents_and_driver_licenses(): void
    {
        $response = $this->get(route('intelligence.reports.export', 'compliance'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Vehicle Document', $content);
        $this->assertStringContainsString('Driver License', $content);
    }
}
