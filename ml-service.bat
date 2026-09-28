@echo off
cd /d "%~dp0ml_service"
if exist ".venv\Scripts\python.exe" (
    ".venv\Scripts\python.exe" -m uvicorn main:app --host 127.0.0.1 --port 8010
) else (
    python -m uvicorn main:app --host 127.0.0.1 --port 8010
)
