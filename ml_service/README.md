# Microfleet scikit-learn Service

This FastAPI service provides the Python/scikit-learn fuel and trip-cost prediction layer for Microfleet.

## Setup

```bash
cd ml_service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 8010
```

Then enable it in Laravel:

```env
ML_SCIKIT_ENABLED=true
ML_SCIKIT_URL=http://127.0.0.1:8010
ML_SCIKIT_TIMEOUT=2
```

If the service is disabled or unreachable, Laravel automatically falls back to the PHP rule-based engine in `app/Support/MlInsights.php`.
