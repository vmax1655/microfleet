# Fleet and Transportation UI Alignment

## UI Positioning

The first screen must read as a fleet and transportation operations console, not a generic finance dashboard. The interface should support branch staff who coordinate field collections, loan releases, KYC visits, cash transfers, fuel control, and transportation cost allocation.

## Primary Navigation Groups

- Fleet and Vehicle Management
- Vehicle Reservation and Dispatch System
- Driver and Trip Performance Monitoring
- Fuel Management
- Transport Cost Analysis and Optimization
- Route Planning and Optimization

## Dashboard Contract

The dashboard must surface these operational signals above generic finance metrics:

- Available Fleet
- Active Dispatches
- Today's Fuel Spend
- Fleet Utilization
- ML Cost Variance
- Today's Dispatch Queue
- Fleet Readiness by vehicle class
- Control alerts for fuel variance, document expiry, and driver license expiry
- Center logistics cost allocation snapshot

## Fleet Screen Contract

### Vehicle Registry

Must show vehicle dispatch readiness:

- Plate number
- Vehicle type
- Depot or yard
- Odometer
- Document validity
- Operational status

### Driver Management

Must show driver assignment readiness:

- Driver name
- License restriction codes
- License expiry
- Assigned vehicle
- Safety/performance score
- Availability status

### Vehicle Reservations

Must show formal MFI field trip requests:

- Reservation number
- Requester
- Purpose: Center Collection, Loan Release, KYC Verification, Cash Transfer, Branch Audit
- Center or barangay
- Requested vehicle class
- Time window
- Predicted trip cost
- Approval/assignment status

### Dispatch Board

Must show the gate workflow:

- Approved reservations
- Assigned dispatches
- In-transit vehicles
- Trips waiting for return check-in
- Starting odometer requirement
- Pre-trip check requirement
- Driver license validation
- Return odometer validation

### Trip Monitoring

Must show trip execution and reconciliation:

- Trip number
- Route
- Driver
- Vehicle
- Start and end odometer
- Distance
- Cost
- Status

## Visual Direction

- Use dense operational tables, KPI cards, and kanban-style dispatch lanes.
- Keep colors restrained and status-driven.
- Avoid marketing sections or decorative hero layouts.
- Place the operational workflow in the first viewport.
- Use concise labels that match fleet operations: reservation, dispatch, gate checkout, odometer, route, fuel variance, cost per kilometer, center cost.
