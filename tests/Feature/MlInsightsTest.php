<?php

namespace Tests\Feature;

use App\Models\MlFeedback;
use App\Models\MlPrediction;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\User;
use App\Livewire\Intelligence\Ml;
use App\Livewire\Intelligence\Variance;
use App\Support\MlInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MlInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_based_prediction_can_be_generated_for_a_reservation_when_scikit_is_disabled(): void
    {
        $this->seed();
        config()->set('ml.scikit.enabled', false);

        $reservation = Reservation::where('reservation_number', 'RSV-2026-0420')->firstOrFail();
        $prediction = MlInsights::generateForReservation($reservation, 65);

        $this->assertSame('rule_based_fuel_cost', $prediction->model_name);
        $this->assertGreaterThan(0, (float) $prediction->predicted_fuel_liters);
        $this->assertGreaterThan(0, (float) $prediction->predicted_cost);
        $this->assertArrayHasKey('distance_km', $prediction->feature_payload);
    }

    public function test_scikit_prediction_service_can_generate_for_a_reservation(): void
    {
        $this->seed();
        config()->set('ml.scikit.enabled', true);
        config()->set('ml.scikit.url', 'http://ml.test');

        Http::fake([
            'http://ml.test/predict/fuel-cost' => Http::response([
                'model_name' => 'scikit_learn_fuel_cost',
                'model_version' => 'rf_v1',
                'predicted_fuel_liters' => 1.44,
                'predicted_cost' => 251.20,
                'confidence' => 0.86,
                'feature_payload' => [
                    'estimator' => 'RandomForestRegressor',
                ],
            ]),
        ]);

        $reservation = Reservation::where('reservation_number', 'RSV-2026-0420')->firstOrFail();
        $prediction = MlInsights::generateForReservation($reservation, 65);

        $this->assertSame('scikit_learn_fuel_cost', $prediction->model_name);
        $this->assertSame('rf_v1', $prediction->model_version);
        $this->assertSame(1.44, round((float) $prediction->predicted_fuel_liters, 2));
        $this->assertSame('python_fastapi_scikit_learn', $prediction->feature_payload['provider']);

        Http::assertSent(fn ($request) => $request->url() === 'http://ml.test/predict/fuel-cost'
            && (float) $request['distance_km'] > 0
            && (float) $request['fuel_price'] === 65.0);
    }

    public function test_scikit_prediction_falls_back_to_rule_based_when_service_fails(): void
    {
        $this->seed();
        config()->set('ml.scikit.enabled', true);
        config()->set('ml.scikit.url', 'http://ml.test');

        Http::fake([
            'http://ml.test/predict/fuel-cost' => Http::response(['error' => 'offline'], 503),
        ]);

        $reservation = Reservation::where('reservation_number', 'RSV-2026-0420')->firstOrFail();
        $prediction = MlInsights::generateForReservation($reservation, 65);

        $this->assertSame('rule_based_fuel_cost', $prediction->model_name);
        $this->assertSame('php_rule_based_fallback', $prediction->feature_payload['provider']);
    }

    public function test_ml_screen_run_prediction_action_updates_reservation_estimate(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create(['role' => 'Super Admin']));

        Livewire::test(Ml::class)
            ->set('reservation', 'RSV-2026-0420')
            ->set('fuel_price', '70')
            ->call('runPrediction')
            ->assertHasNoErrors();

        $reservation = Reservation::where('reservation_number', 'RSV-2026-0420')->firstOrFail();

        $this->assertGreaterThan(0, (float) $reservation->predicted_fuel_liters);
        $this->assertGreaterThan(0, (float) $reservation->predicted_cost);
    }

    public function test_ml_screen_predicts_with_default_selected_reservation(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create(['role' => 'Super Admin']));

        $defaultReservation = Reservation::latest('scheduled_start_at')->value('reservation_number');

        Livewire::test(Ml::class)
            ->assertSet('reservation', $defaultReservation)
            ->call('runPrediction')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ml_predictions', [
            'reservation_id' => Reservation::where('reservation_number', $defaultReservation)->value('id'),
        ]);
    }

    public function test_variance_rows_use_actual_fuel_and_cost_outcomes(): void
    {
        $this->seed();

        $row = MlInsights::varianceRows()->firstWhere('trip', 'TRP-2026-0180');

        $this->assertNotNull($row);
        $this->assertSame(38.20, round($row['predictedFuel'], 2));
        $this->assertSame(42.50, round($row['actualFuel'], 2));
        $this->assertSame('Moderate Variance', $row['status']);
    }

    public function test_variance_screen_reconciles_completed_trip_actuals(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create(['role' => 'Super Admin']));

        MlPrediction::whereNotNull('trip_id')->delete();

        Livewire::test(Variance::class)
            ->set('trip', 'TRP-2026-0180')
            ->call('reconcileVariance')
            ->assertHasNoErrors();

        $row = MlInsights::varianceRows()->firstWhere('trip', 'TRP-2026-0180');

        $this->assertNotNull($row);
        $this->assertSame(42.50, round($row['actualFuel'], 2));
        $this->assertGreaterThan(0, $row['predictedFuel']);
    }

    public function test_variance_screen_saves_feedback_for_reconciled_prediction(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create(['role' => 'Super Admin']));

        $prediction = MlInsights::reconcileTrip(Trip::where('trip_number', 'TRP-2026-0180')->firstOrFail());

        Livewire::test(Variance::class)
            ->set('prediction', (string) $prediction->id)
            ->set('outcome', 'Route deviation')
            ->set('feedback_type', 'variance_review')
            ->set('notes', 'Driver confirmed alternate route due to road closure.')
            ->call('saveFeedback')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ml_feedback', [
            'ml_prediction_id' => $prediction->id,
            'outcome' => 'Route deviation',
        ]);
    }

    public function test_efficiency_rows_score_vehicles_from_operations_data(): void
    {
        $this->seed();

        $rows = MlInsights::efficiencyRows();

        $this->assertTrue($rows->contains('vehicle', 'VAN-022'));
        $this->assertTrue($rows->every(fn ($row) => $row['score'] >= 0 && $row['score'] <= 100));
    }

    public function test_ml_feedback_can_be_captured_against_prediction(): void
    {
        $this->seed();

        $prediction = MlPrediction::whereNotNull('trip_id')->firstOrFail();
        $user = User::factory()->create(['role' => 'Fleet Manager']);

        $feedback = MlFeedback::create([
            'ml_prediction_id' => $prediction->id,
            'user_id' => $user->id,
            'feedback_type' => 'variance_review',
            'outcome' => 'valid anomaly',
            'notes' => 'Route deviation confirmed by dispatcher.',
        ]);

        $this->assertSame('valid anomaly', $feedback->outcome);
        $this->assertSame($prediction->id, $feedback->prediction->id);
    }
}
