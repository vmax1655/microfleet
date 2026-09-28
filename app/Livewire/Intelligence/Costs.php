<?php

namespace App\Livewire\Intelligence;

use App\Models\TransportCost;
use App\Models\Trip;
use App\Support\FleetReports;
use App\Support\Rbac;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Transport Cost Analysis')]
class Costs extends Component
{
    public string $selected_trip = 'all';
    public string $bannerMessage = '';

    public function mount(): void
    {
        $this->selected_trip = 'all';
    }

    public function openRecalculateModal(): void
    {
        $this->selected_trip = 'all';
        $this->dispatch('open-modal', 'recalculate-cost');
    }

    public function recalculate(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'intelligence.costs', 'edit'), 403);

        if ($this->selected_trip && $this->selected_trip !== 'all') {
            $trip = Trip::where('trip_number', $this->selected_trip)->first();
            if ($trip) {
                $this->syncTripCost($trip);
                $this->bannerMessage = "Cost rollup for Trip {$trip->trip_number} successfully recalculated!";
            }
        } else {
            $count = $this->syncAllTripCosts();
            $this->bannerMessage = "Transport cost analysis successfully synchronized for {$count} trip(s)!";
        }

        $this->dispatch('close-modal');
    }

    public function recalculateSingle(int $tripId): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'intelligence.costs', 'edit'), 403);

        $trip = Trip::find($tripId);
        if ($trip) {
            $this->syncTripCost($trip);
            $this->bannerMessage = "Trip {$trip->trip_number} cost breakdown successfully updated!";
        }
    }

    public function recalculateAll(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'intelligence.costs', 'edit'), 403);

        $count = $this->syncAllTripCosts();
        $this->bannerMessage = "All transport cost rollups successfully recalculated ({$count} trips)!";
    }

    private function syncAllTripCosts(): int
    {
        $trips = Trip::with(['fuelTransactions', 'expenses', 'transportCost', 'dispatch.reservation.route', 'route', 'vehicle.type'])->get();
        foreach ($trips as $trip) {
            $this->syncTripCost($trip);
        }
        return $trips->count();
    }

    private function syncTripCost(Trip $trip): TransportCost
    {
        $trip->loadMissing(['fuelTransactions', 'expenses', 'transportCost', 'dispatch.reservation.route', 'route', 'vehicle.type']);

        $fuelCost = (float) $trip->fuelTransactions->sum('total_cost');

        $expenseCost = (float) $trip->expenses
            ->whereIn('approval_status', ['approved', 'checked', 'pending'])
            ->sum('amount');

        $distance = max((float) ($trip->distance_km ?? 0), 0);

        // Standard maintenance allocation per kilometer based on vehicle classification
        $maintRate = match (strtolower($trip->vehicle?->type?->name ?? '')) {
            'motorcycle' => 1.25,
            'passenger van' => 2.50,
            'utility pickup' => 2.75,
            'light truck' => 3.25,
            default => 2.25,
        };

        $maintenance = round($distance * $maintRate, 2);
        if ($trip->transportCost && (float) $trip->transportCost->maintenance_allocation > 0 && $maintenance == 0) {
            $maintenance = (float) $trip->transportCost->maintenance_allocation;
        }

        $total = round($fuelCost + $expenseCost + $maintenance, 2);
        $costPerKm = $distance > 0 ? round($total / $distance, 2) : 0;

        $centerCode = $trip->route?->center_code
            ?? $trip->dispatch?->reservation?->route?->center_code
            ?? ($trip->transportCost?->center_code ?? 'CTR-01');

        return TransportCost::updateOrCreate(
            ['trip_id' => $trip->id],
            [
                'fuel_cost' => $fuelCost,
                'expense_cost' => $expenseCost,
                'maintenance_allocation' => $maintenance,
                'total_cost' => $total,
                'cost_per_km' => $costPerKm,
                'center_code' => $centerCode,
                'status' => 'posted',
            ]
        );
    }

    public function exportCsv()
    {
        $costs = TransportCost::with(['trip.vehicle', 'trip.driver.user', 'trip.route'])->latest()->get();
        $filename = 'transport-cost-analysis-' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($costs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Trip Number', 'Center Code', 'Vehicle Plate', 'Driver', 'Distance (km)', 'Fuel Cost (PHP)', 'Trip Expenses (PHP)', 'Maintenance Alloc (PHP)', 'Total Cost (PHP)', 'Cost / km (PHP)', 'Status']);
            foreach ($costs as $c) {
                fputcsv($file, [
                    $c->trip?->trip_number ?? 'N/A',
                    $c->center_code ?? 'N/A',
                    $c->trip?->vehicle?->plate_number ?? 'N/A',
                    $c->trip?->driver?->user?->name ?? 'Unassigned',
                    $c->trip?->distance_km ?? 0,
                    number_format((float) $c->fuel_cost, 2, '.', ''),
                    number_format((float) $c->expense_cost, 2, '.', ''),
                    number_format((float) $c->maintenance_allocation, 2, '.', ''),
                    number_format((float) $c->total_cost, 2, '.', ''),
                    number_format((float) $c->cost_per_km, 2, '.', ''),
                    $c->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $costs = TransportCost::query()
            ->with(['trip.vehicle.type', 'trip.driver.user', 'trip.route'])
            ->latest('id')
            ->get();

        $trips = Trip::with('route')->latest('id')->get();
        $tripOptions = $trips->mapWithKeys(function ($t) {
            $center = $t->route?->center_code ? " ({$t->route->center_code})" : '';
            return [$t->trip_number => "{$t->trip_number}{$center} — {$t->status}"];
        })->all();

        return view('livewire.intelligence.costs', [
            'costs'               => $costs,
            'summary'             => FleetReports::costSummary($costs),
            'tripOptions'         => $tripOptions,
            'canRecalculateCosts' => Rbac::allowsRoute(auth()->user()?->role, 'intelligence.costs', 'edit'),
        ]);
    }
}
