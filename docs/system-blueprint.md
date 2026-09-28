# Microfleet System Blueprint

## Canonical Module Scope

Microfleet is limited to the Fleet & Transportation Management subsystem. The system must expose only these modules:

1. Fleet and Vehicle Management
2. Vehicle Reservation and Dispatch System
3. Driver and Trip Performance Monitoring
4. Fuel Management
5. Transport Cost Analysis and Optimization
6. Route Planning and Optimization

## 1. Workflow

### Core Operational Flow

1. User signs in and is routed by role.
2. Admin/Fleet Manager registers vehicles, drivers, depots, fuel vendors, maintenance shops, and system users.
3. Dispatcher creates a trip request or receives one from an external source.
4. System checks vehicle availability, driver availability, vehicle condition, license validity, and policy constraints.
5. Dispatcher assigns a driver and vehicle.
6. Driver accepts assignment, performs pre-trip inspection, and starts trip.
7. Trip progress is updated through manual status changes or GPS/telematics integration.
8. Driver completes trip, submits odometer, fuel, route notes, and incident details if any.
9. System updates vehicle mileage, driver logs, fuel usage, maintenance triggers, and trip history.
10. Maintenance module creates alerts/work orders when thresholds or inspection failures are reached.
11. Finance/Admin reviews operational costs.
12. Managers view reports, KPIs, alerts, and ML-based recommendations.

### Vehicle Lifecycle

1. Vehicle is created.
2. Vehicle documents are uploaded and tracked.
3. Vehicle is marked available.
4. Vehicle is assigned to trips.
5. Fuel, odometer, inspection, maintenance, and incident records accumulate.
6. Vehicle may be moved to maintenance, inactive, retired, or sold.

### Maintenance Lifecycle

1. Preventive rule, inspection issue, mileage threshold, or manual request creates maintenance alert.
2. Fleet Manager or Mechanic opens work order.
3. Mechanic updates diagnosis, parts, labor, cost, and completion status.
4. Vehicle status changes automatically based on work order state.
5. Completed maintenance updates vehicle history and next service schedule.

## 2. Modules

### Fleet and Vehicle Management

- Vehicle registry for motorcycles, multi-cabs, utility vans, and service vehicles
- Vehicle documents, registration, LTO, insurance, and permit tracking
- Depot and yard assignment
- Vehicle readiness status: Available, Reserved, Dispatched, In Transit, Under Maintenance, Unavailable, Inactive
- Preventive maintenance visibility
- Odometer and asset lifecycle tracking

### Vehicle Reservation and Dispatch System

- Vehicle reservation requests
- MFI field purpose classification: Center Collection, Loan Release, KYC Verification, Cash Transfer, Branch Audit
- Requested center/barangay and route
- Vehicle class request
- Reservation approval workflow
- Vehicle and driver assignment
- Gate checkout with starting odometer
- Return check-in with ending odometer

### Driver and Trip Performance Monitoring

- Driver profile and license restriction codes
- License and medical clearance expiry tracking
- Driver availability and assignment status
- Trip monitoring
- Trip duration, distance, safety, and fuel-efficiency indicators
- Driver scorecards

### Fuel Management

- Fuel transaction recording
- Pump receipt, station, liters, unit price, total cost, and odometer capture
- Fuel cost and km/L calculations
- Fuel anomaly and variance detection
- Fuel spend by vehicle, route, and depot

### Transport Cost Analysis and Optimization

- Trip expense vouchers
- Fuel, tolls, parking, per diem, emergency repair, and maintenance allocation
- Cost per kilometer
- Cost per center/barangay route
- Prediction versus actual variance
- ML fuel and cost estimate integration
- Exportable transport cost reports

### Route Planning and Optimization

- Center route catalog
- Depot-to-center route definitions
- Planned distance and estimated duration
- Standard route costs
- Route utilization and inefficiency reporting
- Route optimization recommendations without requiring paid map APIs

## 3. RBAC

### Roles

| Role | Purpose |
| --- | --- |
| Super Admin | Full system control and configuration |
| Fleet Manager | Owns fleet operations, approvals, reports, and maintenance decisions |
| Dispatcher | Creates and manages trip assignments |
| Driver | Views assigned trips and submits operational updates |
| Mechanic | Handles inspections, work orders, and maintenance updates |


### Permission Rules

- Super Admin can manage all records and settings.
- Fleet Manager can approve operational records, manage fleet data, and view all reports.
- Dispatcher can create and update trips but cannot delete completed trips.
- Driver can only access assigned trips and personal driver records.
- Mechanic can update inspections and maintenance records but cannot assign trips.
- Finance can view and update cost-related records but cannot modify dispatch decisions.
- Auditor has read-only access and cannot create, update, or delete business records.

## 4. CRUD Matrix

| Entity | Super Admin | Fleet Manager | Dispatcher | Driver | Mechanic | 
| --- | --- | --- | --- | --- | --- | --- | --- |
| Users | CRUD | R | - | - | - | - | R |
| Roles/Permissions | CRUD | R | - | - | - | - | R |
| Vehicles | CRUD | CRUD | R | R assigned | R | R | R |
| Vehicle Documents | CRUD | CRUD | R | R assigned | R | R | R |
| Drivers | CRUD | CRUD | R | R self | R | R | R |
| Trips | CRUD | CRUD | CRUD | RU assigned | R | R | R |
| Inspections | CRUD | CRUD | R | CR assigned | CRUD | R | R |
| Fuel Logs | CRUD | CRUD | CRU assigned | CR assigned | R | CRUD | R |
| Maintenance Alerts | CRUD | CRUD | R | R assigned | CRUD | R | R |
| Work Orders | CRUD | CRUD | R | R assigned | CRUD | RU cost | R |
| Incidents | CRUD | CRUD | CRU assigned | CR assigned | RU repair | R | R |
| Parts Inventory | CRUD | CRUD | R | - | CRU | RU cost | R |
| Vendors | CRUD | CRUD | R | - | R | CRUD | R |
| Reports | CRUD | CRUD | R assigned | R self | R maintenance | R financial | R |
| Audit Logs | R | R | - | - | - | - | R |

Legend: C = Create, R = Read, U = Update, D = Delete.

## 5. Database

Target database: MySQL/MariaDB for XAMPP compatibility.

### Core Tables

#### users

- id
- name
- email
- password_hash
- role_id
- status
- last_login_at
- created_at
- updated_at

#### roles

- id
- name
- description
- created_at
- updated_at

#### permissions

- id
- module
- action
- description

#### role_permissions

- id
- role_id
- permission_id

#### depots

- id
- name
- address
- latitude
- longitude
- status

#### vehicles

- id
- depot_id
- plate_number
- vin
- vehicle_type
- make
- model
- year
- color
- fuel_type
- current_odometer
- status
- acquisition_date
- retired_at
- created_at
- updated_at

#### vehicle_documents

- id
- vehicle_id
- document_type
- document_number
- issued_at
- expires_at
- file_path
- status

#### drivers

- id
- user_id
- employee_number
- license_number
- license_class
- license_expires_at
- phone
- availability_status
- created_at
- updated_at

#### trips

- id
- trip_number
- requested_by_user_id
- dispatcher_user_id
- driver_id
- vehicle_id
- origin
- destination
- scheduled_start_at
- scheduled_end_at
- actual_start_at
- actual_end_at
- start_odometer
- end_odometer
- status
- purpose
- notes
- created_at
- updated_at

#### inspections

- id
- trip_id
- vehicle_id
- driver_id
- inspection_type
- result
- odometer
- notes
- inspected_at

#### inspection_items

- id
- inspection_id
- item_name
- status
- remarks

#### fuel_logs

- id
- vehicle_id
- driver_id
- trip_id
- vendor_id
- fuel_type
- liters
- unit_cost
- total_cost
- odometer
- fueled_at
- receipt_file_path

#### maintenance_rules

- id
- vehicle_type
- rule_name
- mileage_interval
- day_interval
- description
- is_active

#### maintenance_alerts

- id
- vehicle_id
- source_type
- source_id
- severity
- title
- description
- status
- created_at
- resolved_at

#### work_orders

- id
- work_order_number
- vehicle_id
- mechanic_user_id
- vendor_id
- maintenance_alert_id
- diagnosis
- labor_cost
- parts_cost
- total_cost
- status
- opened_at
- completed_at

#### parts

- id
- sku
- name
- description
- unit_cost
- stock_quantity
- reorder_level
- status

#### work_order_parts

- id
- work_order_id
- part_id
- quantity
- unit_cost
- total_cost

#### incidents

- id
- trip_id
- vehicle_id
- driver_id
- incident_type
- severity
- description
- location
- occurred_at
- status
- resolution_notes

#### vendors

- id
- name
- vendor_type
- contact_name
- phone
- email
- address
- status

#### audit_logs

- id
- user_id
- action
- entity_type
- entity_id
- old_values_json
- new_values_json
- ip_address
- created_at

#### ml_predictions

- id
- model_name
- entity_type
- entity_id
- prediction_type
- score
- explanation_json
- generated_at

## 6. Cross-Module Relationships

- Fleet and Vehicle Management owns depots, vehicle types, vehicles, vehicle documents, and maintenance records.
- Vehicle Reservation and Dispatch System consumes vehicle type, route, requester, vehicle availability, and driver availability data.
- A reservation produces one or more dispatch records. The active dispatch links the approved reservation to a specific vehicle, driver, and dispatcher.
- A dispatch produces a trip record when the vehicle leaves the depot. The trip must carry the same route context as the reservation unless a dispatcher records a justified route change.
- Driver and Trip Performance Monitoring consumes dispatch, trip, route, vehicle, driver, odometer, location, and completion data.
- Fuel Management records fuel transactions against vehicle, driver, and trip when applicable.
- Transport Cost Analysis and Optimization consumes trip distance, fuel transactions, trip expenses, maintenance allocation, route, and center code.
- Route Planning and Optimization owns depot-to-center route definitions. Reservations, trips, ML predictions, and cost reports reference these routes.
- ML predictions are linked to reservations before dispatch and may be linked to trips after completion for predicted-versus-actual variance.
- Live GPS/trip monitoring stores trip location points against trips, allowing dispatch and driver performance screens to share the same in-transit data.

### Integration Contracts

| Source Module | Integrated Module | Required Link |
| --- | --- | --- |
| Fleet and Vehicle Management | Reservation and Dispatch | Reservation requested vehicle type must match assigned vehicle type |
| Reservation and Dispatch | Driver and Trip Performance | Dispatch creates trip with vehicle, driver, route, and odometer data |
| Route Planning and Optimization | Reservation and Dispatch | Reservation must reference a planned route when destination is a known center |
| Route Planning and Optimization | Transport Cost Analysis | Route center code and planned distance feed cost-per-center and cost-per-km reporting |
| Fuel Management | Transport Cost Analysis | Fuel transaction total cost rolls up into trip transport cost |
| Driver and Trip Performance | Transport Cost Analysis | Trip distance and completion status control cost finalization |
| Driver and Trip Performance | Live Trip Monitoring | In-transit trip location points drive real-time map markers |
| ML Prediction | Transport Cost Analysis | Predicted fuel and cost are reconciled with actual fuel and cost |

## 7. Business Rules

### Assignment Rules

- A vehicle cannot be assigned if its status is maintenance, inactive, retired, or unavailable.
- A driver cannot be assigned if unavailable, suspended, or license-expired.
- A vehicle cannot be assigned to overlapping active trips.
- A driver cannot be assigned to overlapping active trips.
- A trip cannot start without a passed pre-trip inspection unless Fleet Manager override is recorded.

### Vehicle Rules

- Plate number and VIN must be unique.
- Vehicle odometer cannot decrease.
- Expired registration, insurance, or required permit marks the vehicle as restricted.
- Retired vehicles cannot be assigned to new trips.

### Driver Rules

- License expiry must be monitored.
- Expired license blocks new assignments.
- Drivers can only modify trips assigned to them.

### Trip Rules

- Completed trips cannot be deleted.
- End odometer must be greater than or equal to start odometer.
- Trip status flow: requested -> scheduled -> accepted -> in_progress -> completed.
- Exception statuses: cancelled, rejected, delayed.

### Maintenance Rules

- Maintenance alert is created when mileage or time threshold is reached.
- Critical maintenance alert makes vehicle unavailable.
- Work order completion requires cost summary and completion notes.
- Parts inventory must decrease when parts are used.

### Fuel Rules

- Fuel log odometer must be within valid range for the vehicle.
- Fuel cost is calculated as liters multiplied by unit cost.
- Fuel logs with abnormal consumption are flagged for review.

### Audit Rules

- Create, update, delete, approval, override, and login events must be logged.
- Audit logs are append-only.
- Sensitive changes require user ID, timestamp, and IP address.

## 8. ML Architecture

### Data Sources

- trips
- vehicles
- drivers
- inspections
- fuel_logs
- maintenance_alerts
- work_orders
- incidents
- vehicle_documents

### Feature Store

Key features:

- Vehicle age
- Current odometer
- Mileage since last service
- Days since last service
- Fuel efficiency trend
- Trip frequency
- Incident count
- Failed inspection count
- Downtime history
- Maintenance cost trend
- Driver incident frequency

### Models

#### Predictive Maintenance

- Input: vehicle mileage, service history, inspection results, incidents, age, downtime.
- Output: maintenance risk score and recommended service window.
- Initial approach: rule-based scoring.
- Later approach: gradient boosting classifier/regressor.

#### Fuel Anomaly Detection

- Input: liters, odometer delta, route type, vehicle type, driver, fuel history.
- Output: anomaly score and review flag.
- Initial approach: rolling average threshold.
- Later approach: isolation forest or statistical anomaly detection.

#### Driver Risk Scoring

- Input: incident count, inspection failures, harsh event telemetry if available, trip delays.
- Output: driver risk score.
- Initial approach: weighted scorecard.
- Later approach: supervised risk model.

#### Trip Duration Estimation

- Input: origin, destination, schedule, distance, vehicle type, driver, historical duration.
- Output: estimated completion time.
- Initial approach: historical average.
- Later approach: regression model.

### Implemented Scikit-learn Service

- Python/FastAPI service lives in `ml_service/`.
- Main endpoint: `POST /predict/fuel-cost`.
- Health endpoint: `GET /health`.
- Current estimator: scikit-learn `RandomForestRegressor` wrapped by `MultiOutputRegressor`.
- Laravel client is integrated through `App\Support\MlInsights`.
- Environment switches: `ML_SCIKIT_ENABLED`, `ML_SCIKIT_URL`, and `ML_SCIKIT_TIMEOUT`.
- If the Python service is disabled, unavailable, or returns an invalid response, the PHP rule-based fallback engine is used automatically.

### ML Pipeline

1. Nightly ETL extracts operational records.
2. Feature builder creates aggregate feature tables.
3. Model job generates predictions and writes to `ml_predictions`.
4. Application displays predictions in alerts, dashboards, and reports.
5. User actions and outcomes are captured for model improvement.

## 9. Reports

### Operational Reports

- Active trips
- Vehicle availability
- Driver availability
- Delayed trips
- Trip completion summary
- Vehicle utilization

### Maintenance Reports

- Upcoming preventive maintenance
- Open work orders
- Maintenance cost by vehicle
- Vehicle downtime
- Repeated defects

### Fuel Reports

- Fuel cost by vehicle
- Fuel efficiency by vehicle
- Fuel cost by depot
- Fuel anomaly list
- Vendor fuel spend

### Driver Reports

- Trips completed
- Incident history
- Inspection compliance
- On-time completion
- Driver utilization

### Compliance Reports

- Expiring licenses
- Expiring vehicle documents
- Expired documents
- Audit trail
- Override log

### Executive Dashboard

- Total vehicles
- Available vehicles
- Vehicles in maintenance
- Active trips
- Monthly fuel cost
- Monthly maintenance cost
- Top cost vehicles
- High-risk vehicles
- High-risk drivers

## Initial Build Milestones

### Milestone 1: Foundation

- Database schema
- Authentication
- Roles and permissions
- Admin dashboard shell

### Milestone 2: Fleet Operations

- Vehicle CRUD
- Driver CRUD
- Trip CRUD
- Assignment rules

### Milestone 3: Maintenance and Fuel

- Inspection checklists
- Fuel logs
- Maintenance alerts
- Work orders

### Milestone 4: Reporting

- Operational dashboard
- Cost reports
- Compliance reports
- Export to CSV/PDF

### Milestone 5: ML Insights

- Rule-based prediction engine
- Risk scoring tables
- ML dashboard widgets
- Feedback capture

### Milestone 6: Workflow Verification

- Cross-module data integration checks
- Screen smoke tests for all fleet modules
- Button wiring checks for modal and navigation actions
- Production frontend build verification

### Milestone 7: Operational Workflow Actions

- Vehicle, driver, reservation, fuel, inspection, maintenance, dispatch, and trip check-in forms persist to the database
- Reservation creation generates prediction-backed fuel and cost estimates
- Failed inspections create maintenance alerts and hold vehicles from dispatch
- Work order creation and closure update vehicle availability
- Gate checkout starts in-transit trips and links dispatch, driver, vehicle, route, and odometer data
- Trip check-in posts completed trip status, updates driver/vehicle availability, and creates transport cost rollups

### Milestone 8: Reservation Approval and Dispatch Assignment

- Reservation review can approve or reject pending transport requests
- Dispatch assignment converts approved reservations into assigned dispatch records
- Assignment validates available vehicles, available licensed drivers, matching vehicle class, and duplicate active dispatches
- Reservation, vehicle, and driver statuses update together during assignment
- Gate checkout moves assigned reservations to in-transit status
- Trip check-in completes the reservation, dispatch, trip, cost, vehicle, and driver workflow loop

### Milestone 9: Scikit-learn ML Service Integration

- Python/FastAPI/scikit-learn service added under `ml_service/`
- Laravel ML client calls the Python service when `ML_SCIKIT_ENABLED=true`
- Rule-based PHP ML engine remains as automatic fallback
- ML prediction modal now persists fresh reservation estimates
- Prediction records store provider, service, confidence, estimator, route, fuel price, load, and vehicle efficiency features
