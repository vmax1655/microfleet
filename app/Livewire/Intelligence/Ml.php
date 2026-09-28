<?php

namespace App\Livewire\Intelligence;

use App\Models\MlPrediction;
use App\Models\Reservation;
use App\Support\MlInsights;
use App\Support\Rbac;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('ML Fuel Predictor')]
class Ml extends Component
{
    public string $reservation = '';
    public float|string $fuel_price = '65.00';

    public bool $showBanner = false;
    public string $bannerType = 'success';
    public string $bannerMessage = '';

    public function mount(): void
    {
        $options = MlInsights::reservationOptions();
        $this->reservation = $options[0] ?? '';
    }

    public function runPrediction(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'intelligence.ml', 'edit'), 403);

        if ($this->reservation === '') {
            $this->reservation = MlInsights::reservationOptions()[0] ?? '';
        }

        $data = $this->validate([
            'reservation' => ['required', 'exists:reservations,reservation_number'],
            'fuel_price'  => ['required', 'numeric', 'min:1', 'max:999'],
        ]);

        $reservation = Reservation::with(['route', 'vehicleType'])
            ->where('reservation_number', $data['reservation'])
            ->firstOrFail();

        $prediction = MlInsights::generateForReservation($reservation, (float) $data['fuel_price']);

        // Also cache on the reservation row for quick display
        $reservation->update([
            'predicted_fuel_liters' => $prediction->predicted_fuel_liters,
            'predicted_cost'        => $prediction->predicted_cost,
        ]);

        $this->dispatch('close-modal', 'run-prediction');

        $channel = $prediction->feature_payload['channel'] ?? $prediction->feature_payload['provider'] ?? 'fallback';
        $isScikitLearn = str_contains($channel, 'fastapi') || str_contains($channel, 'python_cli');

        $this->showBanner = true;
        $this->bannerType = 'success';
        $this->bannerMessage = sprintf(
            'Prediction generated for %s: %.2fL / ₱%s (%s).',
            $data['reservation'],
            (float) $prediction->predicted_fuel_liters,
            number_format((float) $prediction->predicted_cost, 2),
            $isScikitLearn ? 'scikit-learn RF model' : 'rule-based fallback'
        );
    }

    public function exportCsv(): StreamedResponse
    {
        $predictions = MlInsights::predictionQueue();

        return response()->streamDownload(function () use ($predictions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Reference', 'Route', 'Model', 'Version',
                'Predicted Liters', 'Predicted Cost',
                'Actual Liters', 'Actual Cost',
                'Variance %', 'Status', 'Generated At',
            ]);

            foreach ($predictions as $row) {
                $reference = $row->reservation?->reservation_number
                    ?? $row->trip?->trip_number
                    ?? '-';
                $route = $row->reservation?->route?->route_code
                    ?? $row->trip?->route?->route_code
                    ?? '-';
                $status = $row->actual_cost ? 'Reconciled' : 'Generated';

                fputcsv($out, [
                    $reference,
                    $route,
                    $row->model_name,
                    $row->model_version,
                    number_format((float) $row->predicted_fuel_liters, 2),
                    number_format((float) $row->predicted_cost, 2),
                    $row->actual_fuel_liters !== null ? number_format((float) $row->actual_fuel_liters, 2) : '',
                    $row->actual_cost !== null ? number_format((float) $row->actual_cost, 2) : '',
                    $row->variance_percent !== null ? number_format((float) $row->variance_percent, 2).'%' : '',
                    $status,
                    $row->generated_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($out);
        }, 'ml-predictions-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function dismissBanner(): void
    {
        $this->showBanner = false;
    }

    public function render()
    {
        return view('livewire.intelligence.ml', [
            'predictions'        => MlInsights::predictionQueue(),
            'riskCards'          => MlInsights::riskCards(),
            'reservationOptions' => MlInsights::reservationOptions(),
            'canRunPrediction'   => Rbac::allowsRoute(auth()->user()?->role, 'intelligence.ml', 'edit'),
        ]);
    }
}
