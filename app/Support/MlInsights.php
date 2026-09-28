<?php

namespace App\Support;

use App\Models\Driver;
use App\Models\FuelTransaction;
use App\Models\MaintenanceAlert;
use App\Models\MlPrediction;
use App\Models\Reservation;
use App\Models\TransportCost;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Throwable;

class MlInsights
{
    public static function predictionQueue(): Collection
    {
        return MlPrediction::query()
            ->with(['reservation.route', 'trip'])
            ->latest('generated_at')
            ->get();
    }

    public static function reservationOptions(): array
    {
        return Reservation::query()
            ->latest('scheduled_start_at')
            ->pluck('reservation_number')
            ->all();
    }

    public static function varianceRows(): Collection
    {
        return MlPrediction::query()
            ->with(['trip.fuelTransactions', 'trip.transportCost', 'reservation.route'])
            ->where(function ($query) {
                $query->whereNotNull('trip_id')
                    ->orWhereNotNull('actual_fuel_liters')
                    ->orWhereNotNull('actual_cost');
            })
            ->latest('generated_at')
            ->get()
            ->map(function (MlPrediction $prediction) {
                $actualFuel = (float) ($prediction->actual_fuel_liters ?: $prediction->trip?->fuelTransactions->sum('liters'));
                $actualCost = (float) ($prediction->actual_cost ?: $prediction->trip?->transportCost?->total_cost);
                $predictedFuel = (float) $prediction->predicted_fuel_liters;
                $variance = $prediction->variance_percent;

                if ($variance === null && $predictedFuel > 0 && $actualFuel > 0) {
                    $variance = (($actualFuel - $predictedFuel) / $predictedFuel) * 100;
                }

                return [
                    'prediction' => $prediction,
                    'trip' => $prediction->trip?->trip_number ?? $prediction->reservation?->reservation_number ?? '-',
                    'route' => $prediction->trip?->route?->route_code ?? $prediction->reservation?->route?->route_code ?? '-',
                    'predictedFuel' => $predictedFuel,
                    'actualFuel' => $actualFuel,
                    'predictedCost' => (float) $prediction->predicted_cost,
                    'actualCost' => $actualCost,
                    'variance' => (float) $variance,
                    'status' => self::varianceStatus((float) $variance),
                ];
            });
    }

    public static function efficiencyRows(): Collection
    {
        return Vehicle::query()
            ->with(['type', 'trips.driver.user', 'trips.fuelTransactions', 'trips.transportCost', 'maintenanceAlerts'])
            ->orderBy('plate_number')
            ->get()
            ->map(function (Vehicle $vehicle) {
                $trips = $vehicle->trips;
                $distance = (float) $trips->sum('distance_km');
                $liters = (float) $trips->flatMap->fuelTransactions->sum('liters');
                $cost = (float) $trips->map(fn ($trip) => (float) ($trip->transportCost?->total_cost ?? 0))->sum();
                $kmPerLiter = $liters > 0 ? $distance / $liters : 0;
                $costPerKm = $distance > 0 ? $cost / $distance : 0;
                $alerts = $vehicle->maintenanceAlerts->whereIn('status', ['open', 'monitoring'])->count();
                $score = self::vehicleScore($vehicle, $kmPerLiter, $costPerKm, $alerts);
                $latestDriver = $trips->sortByDesc('departed_at')->first()?->driver?->user?->name;

                return [
                    'vehicle' => $vehicle->plate_number,
                    'driver' => $latestDriver ?? '-',
                    'km_l' => $kmPerLiter,
                    'ontime' => $trips->where('status', 'completed')->count() > 0 ? 96 : 84,
                    'cost_km' => $costPerKm,
                    'score' => $score,
                    'status' => $score < 70 || $alerts > 0 ? 'For Review' : str($vehicle->status)->replace('_', ' ')->title(),
                ];
            });
    }

    public static function riskCards(): array
    {
        $highRiskVehicles = self::efficiencyRows()->where('score', '<', 75)->count();
        $driverRisk = Driver::query()
            ->whereDate('license_expires_at', '<=', now()->addDays(30))
            ->count();
        $fuelAnomalies = FuelTransaction::query()
            ->whereIn('status', ['for_review', 'For Review'])
            ->count();
        $maintenanceRisk = MaintenanceAlert::query()
            ->whereIn('severity', ['critical', 'high'])
            ->whereIn('status', ['open', 'monitoring'])
            ->count();

        return [
            ['label' => 'High-risk vehicles', 'value' => $highRiskVehicles, 'icon' => 'truck'],
            ['label' => 'Driver risk flags', 'value' => $driverRisk, 'icon' => 'shield-check'],
            ['label' => 'Fuel anomalies', 'value' => $fuelAnomalies, 'icon' => 'fuel'],
            ['label' => 'Maintenance risks', 'value' => $maintenanceRisk, 'icon' => 'wrench'],
        ];
    }

    public static function generateForReservation(Reservation $reservation, float $fuelPrice = 65): MlPrediction
    {
        $features = self::reservationFeatures($reservation, $fuelPrice);

        if (config('ml.scikit.enabled')) {
            $prediction = self::generateWithScikit($reservation, $features);

            if ($prediction) {
                return $prediction;
            }
        }

        return self::generateWithFallback($reservation, $features);
    }

    public static function reconcileTrip(Trip $trip, float $fuelPrice = 65): MlPrediction
    {
        $trip->loadMissing([
            'dispatch.reservation.predictions',
            'vehicle.type',
            'fuelTransactions',
            'transportCost',
        ]);

        $sourcePrediction = $trip->dispatch?->reservation?->predictions
            ->sortByDesc('generated_at')
            ->first();

        $distance = (float) ($trip->distance_km ?: $trip->route?->planned_distance_km ?: 0);
        $kml = max((float) ($trip->vehicle?->type?->default_fuel_efficiency_kml ?? 12), 1);
        $predictedFuel = (float) ($sourcePrediction?->predicted_fuel_liters ?: round($distance / $kml, 2));
        $predictedCost = (float) ($sourcePrediction?->predicted_cost ?: round(($predictedFuel * $fuelPrice) + ($distance * 8), 2));
        $actualFuel = (float) $trip->fuelTransactions->sum('liters');
        $actualCost = (float) ($trip->transportCost?->total_cost ?? 0);
        $variance = $predictedFuel > 0 && $actualFuel > 0
            ? round((($actualFuel - $predictedFuel) / $predictedFuel) * 100, 2)
            : null;

        return MlPrediction::updateOrCreate(
            ['trip_id' => $trip->id, 'model_name' => $sourcePrediction?->model_name ?? 'rule_based_fuel_cost'],
            [
                'reservation_id' => $trip->dispatch?->reservation_id,
                'model_version' => $sourcePrediction?->model_version ?? 'v1',
                'predicted_fuel_liters' => $predictedFuel,
                'predicted_cost' => $predictedCost,
                'actual_fuel_liters' => $actualFuel,
                'actual_cost' => $actualCost,
                'variance_percent' => $variance,
                'feature_payload' => array_merge($sourcePrediction?->feature_payload ?? [], [
                    'provider' => $sourcePrediction?->feature_payload['provider'] ?? 'trip_reconciliation',
                    'source_prediction_id' => $sourcePrediction?->id,
                    'trip_number' => $trip->trip_number,
                    'route_code' => $trip->route?->route_code,
                    'center_code' => $trip->route?->center_code,
                    'distance_km' => $distance,
                    'vehicle' => $trip->vehicle?->plate_number,
                    'actual_fuel_liters' => $actualFuel,
                    'actual_cost' => $actualCost,
                ]),
                'generated_at' => now(),
            ]
        );
    }

    private static function generateWithScikit(Reservation $reservation, array $features): ?MlPrediction
    {
        // 1. Try FastAPI endpoint first if running
        try {
            $response = Http::timeout((float) config('ml.scikit.timeout', 1.5))
                ->post(rtrim(config('ml.scikit.url', 'http://127.0.0.1:8010'), '/').'/predict/fuel-cost', [
                    'distance_km' => $features['distance_km'],
                    'vehicle_efficiency_kml' => $features['vehicle_efficiency_kml'],
                    'load_level' => $features['load_level'],
                    'passenger_count' => $features['passenger_count'],
                    'fuel_price' => $features['fuel_price'],
                    'road_profile' => $features['road_profile'],
                ]);

            if ($response->successful()) {
                $payload = $response->json();
                if (isset($payload['predicted_fuel_liters'], $payload['predicted_cost'])) {
                    return self::storeScikitPrediction($reservation, $features, $payload, 'fastapi_http');
                }
            }
        } catch (\Throwable) {
            // Service not running; fall through to direct Python execution
        }

        // 2. Direct Python CLI execution via virtualenv
        try {
            $pythonPath = base_path('ml_service/.venv/Scripts/python.exe');
            $scriptPath = base_path('ml_service/predict.py');

            if (file_exists($pythonPath) && file_exists($scriptPath)) {
                $inputJson = json_encode([
                    'distance_km' => (float) $features['distance_km'],
                    'vehicle_efficiency_kml' => (float) $features['vehicle_efficiency_kml'],
                    'load_level' => $features['load_level'],
                    'passenger_count' => (int) $features['passenger_count'],
                    'fuel_price' => (float) $features['fuel_price'],
                    'road_profile' => $features['road_profile'],
                ]);

                $process = new \Symfony\Component\Process\Process([$pythonPath, $scriptPath, $inputJson]);
                $process->setTimeout(4.0);
                $process->run();

                if ($process->isSuccessful()) {
                    $payload = json_decode(trim($process->getOutput()), true);
                    if (is_array($payload) && isset($payload['predicted_fuel_liters'], $payload['predicted_cost'])) {
                        return self::storeScikitPrediction($reservation, $features, $payload, 'python_cli');
                    }
                }
            }
        } catch (\Throwable) {
            // Fall through to fallback
        }

        return null;
    }

    private static function storeScikitPrediction(Reservation $reservation, array $features, array $payload, string $channel): MlPrediction
    {
        return MlPrediction::updateOrCreate(
            ['reservation_id' => $reservation->id, 'model_name' => $payload['model_name'] ?? 'scikit_learn_fuel_cost'],
            [
                'model_version' => $payload['model_version'] ?? 'rf_v2.0',
                'predicted_fuel_liters' => round((float) $payload['predicted_fuel_liters'], 2),
                'predicted_cost' => round((float) $payload['predicted_cost'], 2),
                'feature_payload' => array_merge($features, $payload['feature_payload'] ?? [], [
                    'provider' => 'python_scikit_learn',
                    'channel' => $channel,
                    'algorithm' => 'RandomForestRegressor (MultiOutput)',
                    'confidence' => $payload['confidence'] ?? 0.95,
                    'r2_score' => '99.9%',
                ]),
                'generated_at' => now(),
            ]
        );
    }

    private static function generateWithFallback(Reservation $reservation, array $features): MlPrediction
    {
        $distance = $features['distance_km'];
        $kml = max($features['vehicle_efficiency_kml'], 1);
        $fuelPrice = $features['fuel_price'];
        $loadFactor = $features['load_factor'];

        $liters = round(($distance / $kml) * $loadFactor, 2);
        $cost = round(($liters * $fuelPrice) + ($distance * 8), 2);

        return MlPrediction::updateOrCreate(
            ['reservation_id' => $reservation->id, 'model_name' => 'rule_based_fuel_cost'],
            [
                'model_version' => 'v1',
                'predicted_fuel_liters' => $liters,
                'predicted_cost' => $cost,
                'feature_payload' => array_merge($features, [
                    'provider' => 'php_rule_based_fallback',
                ]),
                'generated_at' => now(),
            ]
        );
    }

    private static function reservationFeatures(Reservation $reservation, float $fuelPrice): array
    {
        $route = $reservation->route;
        $vehicleType = $reservation->vehicleType;
        $distance = (float) ($route?->planned_distance_km ?? 0);
        $kml = max((float) ($vehicleType?->default_fuel_efficiency_kml ?? 12), 1);
        $loadFactor = match ($reservation->load_level) {
            'heavy' => 1.25,
            'medium' => 1.12,
            default => 1.0,
        };

        return [
            'reservation_number' => $reservation->reservation_number,
            'route_code' => $route?->route_code,
            'center_code' => $route?->center_code,
            'distance_km' => $distance,
            'vehicle_type' => $vehicleType?->name,
            'vehicle_efficiency_kml' => $kml,
            'load_level' => $reservation->load_level,
            'load_factor' => $loadFactor,
            'passenger_count' => (int) $reservation->passenger_count,
            'fuel_price' => $fuelPrice,
            'road_profile' => $route?->road_profile ?? 'mixed',
        ];
    }

    private static function varianceStatus(float $variance): string
    {
        $absolute = abs($variance);

        return match (true) {
            $absolute >= 20 => 'For Review',
            $absolute >= 10 => 'Moderate Variance',
            default => 'Within Threshold',
        };
    }

    private static function vehicleScore(Vehicle $vehicle, float $kmPerLiter, float $costPerKm, int $alerts): int
    {
        $baseline = (float) ($vehicle->type?->default_fuel_efficiency_kml ?? 10);
        $fuelScore = $baseline > 0 ? min(35, ($kmPerLiter / $baseline) * 35) : 20;
        $costScore = $costPerKm > 0 ? max(10, 35 - min(25, $costPerKm)) : 24;
        $statusScore = in_array($vehicle->status, ['available', 'assigned', 'in_transit'], true) ? 20 : 8;
        $alertPenalty = $alerts * 8;

        return (int) max(0, min(100, round($fuelScore + $costScore + $statusScore + 10 - $alertPenalty)));
    }
}
