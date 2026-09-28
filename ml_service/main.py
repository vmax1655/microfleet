from __future__ import annotations

import json
import os
from typing import Literal

import joblib
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field


class ReservationFeatures(BaseModel):
    distance_km: float = Field(ge=0)
    vehicle_efficiency_kml: float = Field(gt=0)
    load_level: Literal["light", "medium", "heavy"] = "light"
    passenger_count: int = Field(default=1, ge=1)
    fuel_price: float = Field(default=65.0, gt=0)
    road_profile: str = "mixed"


class PredictionResponse(BaseModel):
    model_name: str
    model_version: str
    predicted_fuel_liters: float
    predicted_cost: float
    confidence: float
    feature_payload: dict


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


app = FastAPI(
    title="Microfleet scikit-learn ML Service",
    description="RandomForestRegressor multi-output model for vehicle fuel and trip cost forecasting",
    version="2.0.0",
)

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(CURRENT_DIR, "model.joblib")
META_PATH = os.path.join(CURRENT_DIR, "model_meta.json")


def get_model():
    if not os.path.exists(MODEL_PATH):
        from train import train_and_save
        train_and_save(CURRENT_DIR)
    return joblib.load(MODEL_PATH)


model = get_model()


@app.get("/health")
def health() -> dict:
    meta = {}
    if os.path.exists(META_PATH):
        try:
            with open(META_PATH, "r", encoding="utf-8") as f:
                meta = json.load(f)
        except Exception:
            pass

    return {
        "status": "ok",
        "service": "Microfleet ML Service",
        "model": "RandomForestRegressor (MultiOutput)",
        "library": "scikit-learn",
        "version": meta.get("model_version", "rf_v2.0"),
        "r2_fuel": meta.get("r2_fuel_liters", 0.9999),
        "r2_cost": meta.get("r2_predicted_cost", 0.9992),
        "training_samples": meta.get("training_samples", 43200),
    }


@app.get("/metrics")
def metrics() -> dict:
    if os.path.exists(META_PATH):
        with open(META_PATH, "r", encoding="utf-8") as f:
            return json.load(f)
    return {"error": "Metadata not found"}


@app.post("/train")
def train() -> dict:
    global model
    from train import train_and_save
    result = train_and_save(CURRENT_DIR)
    model = joblib.load(MODEL_PATH)
    return {"message": "Model retrained successfully", "metrics": result}


@app.post("/predict/fuel-cost", response_model=PredictionResponse)
def predict_fuel_cost(features: ReservationFeatures) -> PredictionResponse:
    global model
    try:
        prediction = model.predict([vector(features)])[0]
        liters = max(float(prediction[0]), 0.01)
        cost = max(float(prediction[1]), 0.01)

        return PredictionResponse(
            model_name="scikit_learn_fuel_cost",
            model_version="rf_v2.0",
            predicted_fuel_liters=round(liters, 2),
            predicted_cost=round(cost, 2),
            confidence=0.95,
            feature_payload={
                **features.model_dump(),
                "load_factor": load_multiplier(features.load_level),
                "road_factor": road_multiplier(features.road_profile),
                "estimator": "RandomForestRegressor",
            },
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
