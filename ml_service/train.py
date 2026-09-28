from __future__ import annotations

import json
import os
import time
from typing import Literal

import joblib
import numpy as np
from pydantic import BaseModel, Field
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score, root_mean_squared_error
from sklearn.model_selection import train_test_split
from sklearn.multioutput import MultiOutputRegressor
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler


class ReservationFeatures(BaseModel):
    distance_km: float = Field(ge=0)
    vehicle_efficiency_kml: float = Field(gt=0)
    load_level: Literal["light", "medium", "heavy"] = "light"
    passenger_count: int = Field(default=1, ge=1)
    fuel_price: float = Field(default=65.0, gt=0)
    road_profile: str = "mixed"


def load_multiplier(level: str) -> float:
    return {"heavy": 1.25, "medium": 1.12}.get(level, 1.0)


def road_multiplier(profile: str) -> float:
    return {"rural": 1.06, "mixed": 1.03, "urban": 0.98}.get(profile, 1.0)


def vector(features: ReservationFeatures) -> list[float]:
    return [
        features.distance_km,
        features.vehicle_efficiency_kml,
        load_multiplier(features.load_level),
        min(features.passenger_count, 30),
        features.fuel_price,
        road_multiplier(features.road_profile),
    ]


def generate_dataset() -> tuple[np.ndarray, np.ndarray]:
    rows: list[list[float]] = []
    targets: list[list[float]] = []

    for distance in np.linspace(3, 120, 25):
        for efficiency in [6.5, 8.0, 10.0, 12.5, 15.0, 18.0, 24.0, 32.0]:
            for load in ["light", "medium", "heavy"]:
                for profile in ["urban", "mixed", "rural"]:
                    for passengers in [1, 2, 4, 8, 14, 20]:
                        for fuel_price in [55.0, 62.0, 68.0, 75.0]:
                            f = ReservationFeatures(
                                distance_km=float(distance),
                                vehicle_efficiency_kml=float(efficiency),
                                load_level=load,
                                passenger_count=passengers,
                                fuel_price=float(fuel_price),
                                road_profile=profile,
                            )
                            load_factor = load_multiplier(load)
                            road_factor = road_multiplier(profile)
                            passenger_factor = 1.0 + (min(passengers, 16) - 1) * 0.012
                            liters = (distance / efficiency) * load_factor * road_factor * passenger_factor
                            cost = (liters * fuel_price) + (distance * 8.0)

                            rows.append(vector(f))
                            targets.append([round(liters, 2), round(cost, 2)])

    return np.array(rows), np.array(targets)


def train_and_save(output_dir: str = ".") -> dict:
    print(f"Generating dataset for Microfleet Fuel & Cost Predictor...")
    t0 = time.time()
    X, y = generate_dataset()
    print(f"Dataset generated: {X.shape[0]:,} samples with {X.shape[1]} features in {time.time() - t0:.2f}s")

    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.15, random_state=42)

    pipeline = Pipeline(
        steps=[
            ("scaler", StandardScaler()),
            (
                "regressor",
                MultiOutputRegressor(
                    RandomForestRegressor(
                        n_estimators=180,
                        random_state=42,
                        min_samples_leaf=2,
                        n_jobs=-1,
                    )
                ),
            ),
        ]
    )

    print("Training scikit-learn RandomForestRegressor Pipeline...")
    t_train = time.time()
    pipeline.fit(X_train, y_train)
    train_duration = time.time() - t_train
    print(f"Training completed in {train_duration:.2f}s")

    # Evaluate
    y_pred = pipeline.predict(X_test)
    r2_liters = r2_score(y_test[:, 0], y_pred[:, 0])
    r2_cost = r2_score(y_test[:, 1], y_pred[:, 1])
    mae_liters = mean_absolute_error(y_test[:, 0], y_pred[:, 0])
    mae_cost = mean_absolute_error(y_test[:, 1], y_pred[:, 1])
    rmse_liters = root_mean_squared_error(y_test[:, 0], y_pred[:, 0])
    rmse_cost = root_mean_squared_error(y_test[:, 1], y_pred[:, 1])

    metrics = {
        "model_name": "scikit_learn_fuel_cost",
        "model_version": "rf_v2.0",
        "algorithm": "RandomForestRegressor (MultiOutput)",
        "features": [
            "distance_km",
            "vehicle_efficiency_kml",
            "load_level_multiplier",
            "passenger_count",
            "fuel_price",
            "road_profile_multiplier",
        ],
        "training_samples": int(X_train.shape[0]),
        "test_samples": int(X_test.shape[0]),
        "r2_fuel_liters": round(float(r2_liters), 4),
        "r2_predicted_cost": round(float(r2_cost), 4),
        "mae_fuel_liters": round(float(mae_liters), 4),
        "mae_predicted_cost": round(float(mae_cost), 4),
        "rmse_fuel_liters": round(float(rmse_liters), 4),
        "rmse_predicted_cost": round(float(rmse_cost), 4),
        "trained_at": time.strftime("%Y-%m-%d %H:%M:%S"),
        "training_duration_seconds": round(train_duration, 2),
    }

    print("\n" + "=" * 55)
    print("MODEL EVALUATION RESULTS (scikit-learn):")
    print(f"  R^2 Score (Fuel Liters):  {r2_liters * 100:.2f}%")
    print(f"  R^2 Score (Trip Cost):    {r2_cost * 100:.2f}%")
    print(f"  MAE (Fuel Liters):        {mae_liters:.3f} L")
    print(f"  MAE (Trip Cost):          PHP {mae_cost:.2f}")
    print(f"  RMSE (Trip Cost):         PHP {rmse_cost:.2f}")
    print("=" * 55 + "\n")

    model_path = os.path.join(output_dir, "model.joblib")
    meta_path = os.path.join(output_dir, "model_meta.json")

    joblib.dump(pipeline, model_path)
    with open(meta_path, "w", encoding="utf-8") as f:
        json.dump(metrics, f, indent=2)

    print(f"Saved model to: {os.path.abspath(model_path)}")
    print(f"Saved metadata to: {os.path.abspath(meta_path)}")
    return metrics


if __name__ == "__main__":
    train_and_save()
