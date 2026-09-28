from __future__ import annotations

import json
import os
import sys

import joblib
from pydantic import BaseModel, Field
from typing import Literal


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


def predict(input_data: dict) -> dict:
    current_dir = os.path.dirname(os.path.abspath(__file__))
    model_path = os.path.join(current_dir, "model.joblib")

    if not os.path.exists(model_path):
        from train import train_and_save
        train_and_save(current_dir)

    model = joblib.load(model_path)
    features = ReservationFeatures(**input_data)
    v = vector(features)
    raw = model.predict([v])[0]

    liters = max(float(raw[0]), 0.01)
    cost = max(float(raw[1]), 0.01)

    return {
        "model_name": "scikit_learn_fuel_cost",
        "model_version": "rf_v2.0",
        "predicted_fuel_liters": round(liters, 2),
        "predicted_cost": round(cost, 2),
        "confidence": 0.95,
        "feature_payload": {
            **features.model_dump(),
            "load_factor": load_multiplier(features.load_level),
            "road_factor": road_multiplier(features.road_profile),
            "estimator": "RandomForestRegressor",
        },
    }


if __name__ == "__main__":
    if len(sys.argv) > 1:
        raw_input = sys.argv[1]
        try:
            payload = json.loads(raw_input)
            result = predict(payload)
            print(json.dumps(result))
            sys.exit(0)
        except Exception as e:
            print(json.dumps({"error": str(e)}), file=sys.stderr)
            sys.exit(1)
    else:
        # Default test
        test_payload = {
            "distance_km": 35.0,
            "vehicle_efficiency_kml": 12.0,
            "load_level": "medium",
            "passenger_count": 3,
            "fuel_price": 65.0,
            "road_profile": "mixed",
        }
        print(json.dumps(predict(test_payload), indent=2))
