<?php

namespace App\Livewire\Intelligence;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Support\MlInsights;
use App\Support\Rbac;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Fleet Efficiency Score')]
class Efficiency extends Component
{
    public bool $showBanner = false;
    public string $bannerType = 'success';
    public string $bannerMessage = '';

    public function refreshScores(): void
    {
        $allowed = auth()->user()?->allows('cost-optimization', 'edit')
            || Rbac::allowsRoute(auth()->user()?->role, 'intelligence.efficiency', 'edit');

        abort_unless($allowed, 403);

        // Sync transport costs for completed trips so fuel & cost stats are fresh
        $completedTrips = Trip::with(['fuelTransactions', 'expenses', 'vehicle.type', 'route'])
            ->where('status', 'completed')
            ->get();

        foreach ($completedTrips as $trip) {
            $distance = (float) $trip->distance_km;
            $fuelCost = (float) $trip->fuelTransactions->sum('total_cost');
            $expenseCost = (float) $trip->expenses
                ->whereIn('approval_status', ['approved', 'checked', 'pending'])
                ->sum('amount');

            $maintRate = match ($trip->vehicle?->type?->name) {
                'Motorcycle' => 1.25,
                'Passenger Van' => 2.50,
                'Utility Pickup' => 2.75,
                'Light Truck' => 3.25,
                default => 2.25,
            };

            $maintenance = round($distance * $maintRate, 2);
            $total = round($fuelCost + $expenseCost + $maintenance, 2);

            $trip->transportCost()->updateOrCreate(
                ['trip_id' => $trip->id],
                [
                    'fuel_cost' => $fuelCost,
                    'expense_cost' => $expenseCost,
                    'maintenance_allocation' => $maintenance,
                    'total_cost' => $total,
                    'cost_per_km' => $distance > 0 ? round($total / $distance, 2) : null,
                    'center_code' => $trip->route?->center_code,
                    'status' => 'posted',
                ]
            );
        }

        $scores = MlInsights::efficiencyRows();
        $vehicleCount = $scores->count();
        $avgScore = round($scores->avg('score') ?? 0, 1);

        $this->dispatch('close-modal', 'refresh-efficiency');

        $this->showBanner = true;
        $this->bannerType = 'success';
        $this->bannerMessage = "Fleet efficiency scores recalculated successfully across {$vehicleCount} vehicles! Average fleet score: {$avgScore}%.";
    }

    public function exportCsv(): StreamedResponse
    {
        $scores = MlInsights::efficiencyRows();

        return response()->streamDownload(function () use ($scores) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Vehicle', 'Recent Driver', 'Fuel Efficiency (km/L)',
                'On-time Rate (%)', 'Cost / km (PHP)', 'Efficiency Score (%)', 'Status',
            ]);

            foreach ($scores as $row) {
                fputcsv($out, [
                    $row['vehicle'],
                    $row['driver'],
                    number_format((float) $row['km_l'], 2),
                    number_format((float) $row['ontime'], 1).'%',
                    number_format((float) $row['cost_km'], 2),
                    $row['score'].'%',
                    $row['status'],
                ]);
            }

            fclose($out);
        }, 'fleet-efficiency-scorecard-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function dismissBanner(): void
    {
        $this->showBanner = false;
    }

    public function render()
    {
        return view('livewire.intelligence.efficiency', [
            'scores' => MlInsights::efficiencyRows(),
            'riskCards' => MlInsights::riskCards(),
            'canRefreshScores' => auth()->user()?->allows('cost-optimization', 'edit')
                || Rbac::allowsRoute(auth()->user()?->role, 'intelligence.efficiency', 'edit'),
        ]);
    }
}
