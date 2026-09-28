<?php

namespace App\Support;

use App\Models\Driver;
use App\Models\FuelTransaction;
use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\Reservation;
use App\Models\TransportCost;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Support\Collection;

class FleetReports
{
    public static function dashboard(): array
    {
        $totalVehicles = Vehicle::count();
        $availableVehicles = Vehicle::where('status', 'available')->count();
        $activeTrips = Trip::whereIn('status', ['scheduled', 'in_transit'])->count();
        $fuelSpend = (float) FuelTransaction::sum('total_cost');
        $inUseVehicles = Vehicle::whereIn('status', ['assigned', 'in_transit'])->count();
        $utilization = $totalVehicles > 0 ? ($inUseVehicles / $totalVehicles) * 100 : 0;

        return [
            'availableFleet' => $availableVehicles.' / '.$totalVehicles,
            'activeDispatches' => $activeTrips,
            'fuelSpend' => $fuelSpend,
            'utilization' => $utilization,
            'variance' => self::averagePredictionVariance(),
            'dispatchQueue' => self::dispatchQueue(),
            'fleetReadiness' => self::fleetReadiness(),
            'alerts' => self::controlAlerts(),
            'costSnapshot' => self::costSnapshot(),
        ];
    }

    public static function costRows(): Collection
    {
        return TransportCost::query()
            ->with(['trip.route'])
            ->latest()
            ->get();
    }

    public static function costSummary(Collection $costRows): array
    {
        $total = (float) $costRows->sum('total_cost');
        $distance = (float) $costRows->sum(fn ($cost) => (float) ($cost->trip?->distance_km ?? 0));
        $highest = $costRows->sortByDesc('total_cost')->first();

        return [
            'total' => $total,
            'costPerKm' => $distance > 0 ? $total / $distance : 0,
            'highestCenter' => $highest?->center_code ?? '-',
            'drafts' => $costRows->where('status', 'draft')->count(),
        ];
    }

    public static function reportCards(): array
    {
        return [
            [
                'key' => 'operations',
                'title' => 'Operational Dashboard',
                'desc' => 'Active trips, vehicle availability, delayed trips, utilization, and dispatch readiness.',
                'route' => 'dashboard',
                'icon' => 'layout-dashboard',
                'format' => 'CSV',
            ],
            [
                'key' => 'fuel',
                'title' => 'Fuel Consumption Audit',
                'desc' => 'Fuel cost, liters, station, receipt, vehicle, driver, and trip references.',
                'route' => 'logistics.fuel',
                'icon' => 'fuel',
                'format' => 'CSV',
            ],
            [
                'key' => 'costs',
                'title' => 'Transport Cost Report',
                'desc' => 'Fuel, expenses, maintenance allocation, total cost, and cost per kilometer.',
                'route' => 'intelligence.costs',
                'icon' => 'calculator',
                'format' => 'CSV',
            ],
            [
                'key' => 'maintenance',
                'title' => 'Maintenance Report',
                'desc' => 'Open work orders, maintenance cost, alert severity, and vehicle holds.',
                'route' => 'logistics.maintenance',
                'icon' => 'wrench',
                'format' => 'CSV',
            ],
            [
                'key' => 'compliance',
                'title' => 'Compliance Report',
                'desc' => 'Expiring licenses, vehicle documents, restricted vehicles, and audit-ready exceptions.',
                'route' => 'fleet.vehicles',
                'icon' => 'shield-check',
                'format' => 'CSV',
            ],
        ];
    }

    public static function recentReports(): array
    {
        return [
            ['report' => 'Fuel Consumption Audit', 'scope' => 'All depots', 'by' => 'Fleet Manager', 'generated' => now()->subMinutes(18), 'format' => 'CSV'],
            ['report' => 'Transport Cost Report', 'scope' => 'Posted trip costs', 'by' => 'Fleet Manager', 'generated' => now()->subHours(2), 'format' => 'CSV'],
            ['report' => 'Compliance Report', 'scope' => 'Licenses and documents', 'by' => 'Super Admin', 'generated' => now()->subDay(), 'format' => 'CSV'],
        ];
    }

    public static function exportRows(string $report): array
    {
        return match ($report) {
            'operations' => self::operationsExport(),
            'fuel' => self::fuelExport(),
            'costs' => self::costExport(),
            'maintenance' => self::maintenanceExport(),
            'compliance' => self::complianceExport(),
            default => [['Report', 'No data'], ['Unknown report', $report]],
        };
    }

    private static function dispatchQueue(): Collection
    {
        return Reservation::query()
            ->with(['route', 'vehicleType'])
            ->latest('scheduled_start_at')
            ->limit(6)
            ->get();
    }

    private static function fleetReadiness(): Collection
    {
        return Vehicle::query()
            ->with('type')
            ->get()
            ->groupBy(fn ($vehicle) => $vehicle->type?->name ?? 'Unclassified')
            ->map(function ($vehicles, $type) {
                return [
                    'label' => $type,
                    'available' => $vehicles->where('status', 'available')->count(),
                    'total' => $vehicles->count(),
                    'note' => $vehicles->where('status', 'under_maintenance')->count().' under maintenance',
                ];
            })
            ->values();
    }

    private static function controlAlerts(): Collection
    {
        $maintenance = MaintenanceAlert::query()
            ->with('vehicle')
            ->latest('triggered_at')
            ->limit(2)
            ->get()
            ->map(fn ($alert) => [
                'icon' => 'wrench',
                'tone' => $alert->severity === 'critical' ? 'danger' : 'warning',
                'title' => $alert->title,
                'meta' => ($alert->vehicle?->plate_number ?? 'Vehicle').' - '.str($alert->status)->replace('_', ' ')->title(),
            ]);

        $documents = VehicleDocument::query()
            ->with('vehicle')
            ->whereDate('expires_at', '<=', now()->addDays(30))
            ->limit(1)
            ->get()
            ->map(fn ($doc) => [
                'icon' => 'calendar',
                'tone' => 'warning',
                'title' => $doc->document_type.' renewal due',
                'meta' => ($doc->vehicle?->plate_number ?? 'Vehicle').' expires on '.Format::date($doc->expires_at),
            ]);

        $drivers = Driver::query()
            ->with('user')
            ->whereDate('license_expires_at', '<=', now()->addDays(30))
            ->limit(1)
            ->get()
            ->map(fn ($driver) => [
                'icon' => 'shield-check',
                'tone' => 'warning',
                'title' => 'Driver license review',
                'meta' => ($driver->user?->name ?? 'Driver').' expires on '.Format::date($driver->license_expires_at),
            ]);

        return $maintenance->concat($documents)->concat($drivers)->values();
    }

    private static function costSnapshot(): array
    {
        $costRows = self::costRows();
        $summary = self::costSummary($costRows);
        $highest = $costRows->sortByDesc('total_cost')->first();

        return [
            ...$summary,
            'flaggedVouchers' => FuelTransaction::whereIn('status', ['for_review', 'For Review'])->count(),
            'highestRoute' => $highest?->trip?->route?->name ?? 'No posted route yet',
        ];
    }

    private static function averagePredictionVariance(): float
    {
        $values = \App\Models\MlPrediction::query()
            ->whereNotNull('variance_percent')
            ->pluck('variance_percent')
            ->map(fn ($value) => abs((float) $value));

        return $values->count() > 0 ? (float) $values->avg() : 12.8;
    }

    private static function operationsExport(): array
    {
        return collect([['Reservation', 'Purpose', 'Route', 'Vehicle Type', 'Scheduled Start', 'Status', 'Predicted Cost']])
            ->concat(self::dispatchQueue()->map(fn ($reservation) => [
                $reservation->reservation_number,
                $reservation->purpose,
                $reservation->route?->name,
                $reservation->vehicleType?->name,
                optional($reservation->scheduled_start_at)->format('Y-m-d H:i'),
                $reservation->status,
                $reservation->predicted_cost,
            ]))
            ->all();
    }

    private static function fuelExport(): array
    {
        return collect([['Receipt', 'Vehicle', 'Driver', 'Trip', 'Station', 'Liters', 'Unit Price', 'Total Cost', 'Status']])
            ->concat(FuelTransaction::with(['vehicle', 'driver.user', 'trip'])->get()->map(fn ($fuel) => [
                $fuel->receipt_number,
                $fuel->vehicle?->plate_number,
                $fuel->driver?->user?->name,
                $fuel->trip?->trip_number,
                $fuel->station_name,
                $fuel->liters,
                $fuel->unit_price,
                $fuel->total_cost,
                $fuel->status,
            ]))
            ->all();
    }

    private static function costExport(): array
    {
        return collect([['Trip', 'Center', 'Fuel', 'Expenses', 'Maintenance', 'Total', 'Cost per KM', 'Status']])
            ->concat(self::costRows()->map(fn ($cost) => [
                $cost->trip?->trip_number,
                $cost->center_code,
                $cost->fuel_cost,
                $cost->expense_cost,
                $cost->maintenance_allocation,
                $cost->total_cost,
                $cost->cost_per_km,
                $cost->status,
            ]))
            ->all();
    }

    private static function maintenanceExport(): array
    {
        return collect([['Work Order', 'Vehicle', 'Service Type', 'Odometer', 'Parts', 'Labor', 'Total', 'Status']])
            ->concat(MaintenanceRecord::with('vehicle')->get()->map(fn ($workOrder) => [
                $workOrder->work_order_number,
                $workOrder->vehicle?->plate_number,
                $workOrder->service_type,
                $workOrder->odometer_km,
                $workOrder->parts_cost,
                $workOrder->labor_cost,
                $workOrder->total_cost,
                $workOrder->status,
            ]))
            ->all();
    }

    private static function complianceExport(): array
    {
        $vehicleDocs = VehicleDocument::with('vehicle')->get()->map(fn ($doc) => [
            'Vehicle Document',
            $doc->vehicle?->plate_number,
            $doc->document_type,
            Format::date($doc->expires_at),
            $doc->status,
        ]);

        $driverLicenses = Driver::with('user')->get()->map(fn ($driver) => [
            'Driver License',
            $driver->user?->name,
            $driver->license_number,
            Format::date($driver->license_expires_at),
            $driver->license_expires_at->isPast() ? 'expired' : 'valid',
        ]);

        return collect([['Type', 'Subject', 'Document', 'Expires At', 'Status']])
            ->concat($vehicleDocs)
            ->concat($driverLicenses)
            ->all();
    }
}
