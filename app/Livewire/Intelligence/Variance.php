<?php

namespace App\Livewire\Intelligence;

use App\Models\MlFeedback;
use App\Models\MlPrediction;
use App\Models\Trip;
use App\Support\MlInsights;
use App\Support\Rbac;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Prediction vs Actual')]
class Variance extends Component
{
    public string $trip = 'all';
    public string $prediction = '';
    public string $outcome = 'Valid anomaly';
    public string $feedback_type = 'variance_review';
    public string $notes = '';
    public string $bannerMessage = '';

    public function mount(): void
    {
        $this->trip = 'all';
    }

    public function openReconcileModal(): void
    {
        $this->trip = 'all';
        $this->dispatch('open-modal', 'reconcile-variance');
    }

    public function openReviewModal(int $predictionId): void
    {
        $this->prediction = (string) $predictionId;
        $this->outcome = 'Valid anomaly';
        $this->feedback_type = 'variance_review';
        $this->notes = '';
        $this->dispatch('open-modal', 'variance-detail');
    }

    public function reconcileVariance(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'intelligence.variance', 'edit'), 403);

        if ($this->trip && $this->trip !== 'all') {
            $trip = Trip::with(['dispatch.reservation.predictions', 'route.reservations.predictions', 'vehicle.type', 'fuelTransactions', 'transportCost'])
                ->where('trip_number', $this->trip)
                ->first();

            if ($trip) {
                $pred = MlInsights::reconcileTrip($trip);
                $this->prediction = (string) $pred->id;
                $this->bannerMessage = "Trip {$trip->trip_number} successfully reconciled with field outcomes (Variance: " . number_format((float) $pred->variance_percent, 1) . "%).";
            }
        } else {
            $trips = Trip::with(['dispatch.reservation.predictions', 'route.reservations.predictions', 'vehicle.type', 'fuelTransactions', 'transportCost'])
                ->whereIn('status', ['completed', 'in_transit'])
                ->get();

            $count = 0;
            foreach ($trips as $t) {
                MlInsights::reconcileTrip($t);
                $count++;
            }

            $this->bannerMessage = "Successfully reconciled prediction variance for all {$count} trips!";
        }

        $this->dispatch('close-modal');
    }

    public function reconcileSingle(string $tripNumber): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'intelligence.variance', 'edit'), 403);

        $trip = Trip::where('trip_number', $tripNumber)->first();
        if ($trip) {
            $pred = MlInsights::reconcileTrip($trip);
            $this->prediction = (string) $pred->id;
            $this->bannerMessage = "Trip {$trip->trip_number} reconciled! Predicted: {$pred->predicted_fuel_liters}L, Actual: {$pred->actual_fuel_liters}L (Variance: " . number_format((float) $pred->variance_percent, 1) . "%).";
        }
    }

    public function saveFeedback(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'intelligence.variance', 'edit'), 403);

        $data = $this->validate([
            'prediction' => ['required', 'exists:ml_predictions,id'],
            'outcome' => ['required', 'string', 'max:80'],
            'feedback_type' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        MlFeedback::create([
            'ml_prediction_id' => $data['prediction'],
            'user_id' => auth()->id(),
            'feedback_type' => $data['feedback_type'],
            'outcome' => $data['outcome'],
            'notes' => $data['notes'],
        ]);

        $this->bannerMessage = "Review feedback saved successfully for model tuning.";
        $this->reset('notes');
        $this->dispatch('close-modal');
    }

    public function exportCsv()
    {
        $rows = MlInsights::varianceRows();
        $filename = 'variance-review-' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Trip / Reservation', 'Route', 'Predicted Fuel (L)', 'Actual Fuel (L)', 'Predicted Cost (PHP)', 'Actual Cost (PHP)', 'Variance (%)', 'Status']);
            foreach ($rows as $r) {
                fputcsv($file, [
                    $r['trip'],
                    $r['route'],
                    $r['predictedFuel'],
                    $r['actualFuel'],
                    $r['predictedCost'],
                    $r['actualCost'],
                    number_format($r['variance'], 2) . '%',
                    $r['status'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $rows = MlInsights::varianceRows();

        if ($this->prediction === '' && $rows->isNotEmpty()) {
            $this->prediction = (string) $rows->first()['prediction']->id;
        }

        $trips = Trip::with('route')->latest('id')->get();
        $tripOptions = $trips->mapWithKeys(function ($t) {
            $center = $t->route?->center_code ? " ({$t->route->center_code})" : '';
            return [$t->trip_number => "{$t->trip_number}{$center} — {$t->status}"];
        })->all();

        $predictions = MlPrediction::query()
            ->with(['trip', 'reservation'])
            ->whereNotNull('trip_id')
            ->latest('generated_at')
            ->get();

        $predictionOptions = $predictions->mapWithKeys(function (MlPrediction $p) {
            $ref = $p->trip?->trip_number ?? ($p->reservation?->reservation_number ?? "ID #{$p->id}");
            $v = $p->variance_percent !== null ? " (" . number_format($p->variance_percent, 1) . "% var)" : '';
            return [$p->id => "{$ref} — {$p->model_name}{$v}"];
        })->all();

        return view('livewire.intelligence.variance', [
            'rows'              => $rows,
            'tripOptions'       => $tripOptions,
            'predictionOptions' => $predictionOptions,
            'canManageVariance' => Rbac::allowsRoute(auth()->user()?->role, 'intelligence.variance', 'edit'),
        ]);
    }
}
