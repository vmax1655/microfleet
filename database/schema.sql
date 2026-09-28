CREATE DATABASE IF NOT EXISTS microfleet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE microfleet;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT NULL,
    UNIQUE KEY permissions_module_action_unique (module, action)
);

CREATE TABLE role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY role_permissions_unique (role_id, permission_id),
    CONSTRAINT role_permissions_role_fk FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT role_permissions_permission_fk FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT users_role_fk FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE depots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address TEXT NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
);

CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    depot_id BIGINT UNSIGNED NULL,
    plate_number VARCHAR(50) NOT NULL UNIQUE,
    vin VARCHAR(100) NULL UNIQUE,
    vehicle_type VARCHAR(100) NOT NULL,
    make VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    year SMALLINT UNSIGNED NULL,
    color VARCHAR(50) NULL,
    fuel_type ENUM('gasoline', 'diesel', 'electric', 'hybrid', 'other') NOT NULL DEFAULT 'gasoline',
    current_odometer DECIMAL(12, 2) NOT NULL DEFAULT 0,
    status ENUM('available', 'assigned', 'maintenance', 'inactive', 'retired') NOT NULL DEFAULT 'available',
    acquisition_date DATE NULL,
    retired_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT vehicles_depot_fk FOREIGN KEY (depot_id) REFERENCES depots(id) ON DELETE SET NULL
);

CREATE TABLE vehicle_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_number VARCHAR(100) NULL,
    issued_at DATE NULL,
    expires_at DATE NULL,
    file_path VARCHAR(255) NULL,
    status ENUM('valid', 'expired', 'pending') NOT NULL DEFAULT 'valid',
    CONSTRAINT vehicle_documents_vehicle_fk FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

CREATE TABLE drivers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    employee_number VARCHAR(100) NULL UNIQUE,
    license_number VARCHAR(100) NOT NULL UNIQUE,
    license_class VARCHAR(50) NULL,
    license_expires_at DATE NOT NULL,
    phone VARCHAR(50) NULL,
    availability_status ENUM('available', 'assigned', 'off_duty', 'suspended') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT drivers_user_fk FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE vendors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    vendor_type ENUM('fuel', 'maintenance', 'parts', 'other') NOT NULL,
    contact_name VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
);

CREATE TABLE trips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_number VARCHAR(100) NOT NULL UNIQUE,
    requested_by_user_id BIGINT UNSIGNED NULL,
    dispatcher_user_id BIGINT UNSIGNED NULL,
    driver_id BIGINT UNSIGNED NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    scheduled_start_at DATETIME NOT NULL,
    scheduled_end_at DATETIME NULL,
    actual_start_at DATETIME NULL,
    actual_end_at DATETIME NULL,
    start_odometer DECIMAL(12, 2) NULL,
    end_odometer DECIMAL(12, 2) NULL,
    status ENUM('requested', 'scheduled', 'accepted', 'in_progress', 'completed', 'cancelled', 'rejected', 'delayed') NOT NULL DEFAULT 'requested',
    purpose TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT trips_requested_by_fk FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT trips_dispatcher_fk FOREIGN KEY (dispatcher_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT trips_driver_fk FOREIGN KEY (driver_id) REFERENCES drivers(id),
    CONSTRAINT trips_vehicle_fk FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE inspections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    driver_id BIGINT UNSIGNED NULL,
    inspection_type ENUM('pre_trip', 'post_trip', 'maintenance', 'ad_hoc') NOT NULL,
    result ENUM('passed', 'failed', 'needs_review') NOT NULL,
    odometer DECIMAL(12, 2) NULL,
    notes TEXT NULL,
    inspected_at DATETIME NOT NULL,
    CONSTRAINT inspections_trip_fk FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
    CONSTRAINT inspections_vehicle_fk FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    CONSTRAINT inspections_driver_fk FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
);

CREATE TABLE inspection_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspection_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    status ENUM('passed', 'failed', 'not_applicable') NOT NULL,
    remarks TEXT NULL,
    CONSTRAINT inspection_items_inspection_fk FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
);

CREATE TABLE fuel_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    driver_id BIGINT UNSIGNED NULL,
    trip_id BIGINT UNSIGNED NULL,
    vendor_id BIGINT UNSIGNED NULL,
    fuel_type ENUM('gasoline', 'diesel', 'electric', 'hybrid', 'other') NOT NULL,
    liters DECIMAL(10, 2) NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(12, 2) NOT NULL,
    odometer DECIMAL(12, 2) NOT NULL,
    fueled_at DATETIME NOT NULL,
    receipt_file_path VARCHAR(255) NULL,
    CONSTRAINT fuel_logs_vehicle_fk FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    CONSTRAINT fuel_logs_driver_fk FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    CONSTRAINT fuel_logs_trip_fk FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
    CONSTRAINT fuel_logs_vendor_fk FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
);

CREATE TABLE maintenance_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_type VARCHAR(100) NOT NULL,
    rule_name VARCHAR(150) NOT NULL,
    mileage_interval DECIMAL(12, 2) NULL,
    day_interval INT UNSIGNED NULL,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE maintenance_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    source_type VARCHAR(100) NULL,
    source_id BIGINT UNSIGNED NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    CONSTRAINT maintenance_alerts_vehicle_fk FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE work_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_order_number VARCHAR(100) NOT NULL UNIQUE,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    mechanic_user_id BIGINT UNSIGNED NULL,
    vendor_id BIGINT UNSIGNED NULL,
    maintenance_alert_id BIGINT UNSIGNED NULL,
    diagnosis TEXT NULL,
    labor_cost DECIMAL(12, 2) NOT NULL DEFAULT 0,
    parts_cost DECIMAL(12, 2) NOT NULL DEFAULT 0,
    total_cost DECIMAL(12, 2) NOT NULL DEFAULT 0,
    status ENUM('open', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
    opened_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    CONSTRAINT work_orders_vehicle_fk FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    CONSTRAINT work_orders_mechanic_fk FOREIGN KEY (mechanic_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT work_orders_vendor_fk FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
    CONSTRAINT work_orders_alert_fk FOREIGN KEY (maintenance_alert_id) REFERENCES maintenance_alerts(id) ON DELETE SET NULL
);

CREATE TABLE parts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    unit_cost DECIMAL(12, 2) NOT NULL DEFAULT 0,
    stock_quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
);

CREATE TABLE work_order_parts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_order_id BIGINT UNSIGNED NOT NULL,
    part_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_cost DECIMAL(12, 2) NOT NULL,
    total_cost DECIMAL(12, 2) NOT NULL,
    CONSTRAINT work_order_parts_work_order_fk FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT work_order_parts_part_fk FOREIGN KEY (part_id) REFERENCES parts(id)
);

CREATE TABLE incidents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    driver_id BIGINT UNSIGNED NULL,
    incident_type VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    description TEXT NOT NULL,
    location VARCHAR(255) NULL,
    occurred_at DATETIME NOT NULL,
    status ENUM('open', 'investigating', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    resolution_notes TEXT NULL,
    CONSTRAINT incidents_trip_fk FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
    CONSTRAINT incidents_vehicle_fk FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    CONSTRAINT incidents_driver_fk FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
);

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_values_json JSON NULL,
    new_values_json JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT audit_logs_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE ml_predictions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    prediction_type VARCHAR(100) NOT NULL,
    score DECIMAL(10, 4) NOT NULL,
    explanation_json JSON NULL,
    generated_at DATETIME NOT NULL
);

INSERT INTO roles (name, description) VALUES
('Super Admin', 'Full system control and configuration'),
('Fleet Manager', 'Manages fleet operations and views intelligence reports'),
('Dispatcher', 'Creates reservations, manages dispatch, and records fuel transactions'),
('Driver', 'Views vehicles and assigned trips');

INSERT INTO permissions (module, action, description) VALUES
('users', 'create', 'Create users'),
('users', 'read', 'View users'),
('users', 'update', 'Update users'),
('users', 'delete', 'Delete users'),
('vehicles', 'create', 'Create vehicles'),
('vehicles', 'read', 'View vehicles'),
('vehicles', 'update', 'Update vehicles'),
('vehicles', 'delete', 'Delete vehicles'),
('drivers', 'create', 'Create drivers'),
('drivers', 'read', 'View drivers'),
('drivers', 'update', 'Update drivers'),
('drivers', 'delete', 'Delete drivers'),
('trips', 'create', 'Create trips'),
('trips', 'read', 'View trips'),
('trips', 'update', 'Update trips'),
('trips', 'delete', 'Delete trips'),
('inspections', 'create', 'Create inspections'),
('inspections', 'read', 'View inspections'),
('inspections', 'update', 'Update inspections'),
('inspections', 'delete', 'Delete inspections'),
('fuel_logs', 'create', 'Create fuel logs'),
('fuel_logs', 'read', 'View fuel logs'),
('fuel_logs', 'update', 'Update fuel logs'),
('fuel_logs', 'delete', 'Delete fuel logs'),
('maintenance', 'create', 'Create maintenance records'),
('maintenance', 'read', 'View maintenance records'),
('maintenance', 'update', 'Update maintenance records'),
('maintenance', 'delete', 'Delete maintenance records'),
('incidents', 'create', 'Create incidents'),
('incidents', 'read', 'View incidents'),
('incidents', 'update', 'Update incidents'),
('incidents', 'delete', 'Delete incidents'),
('reports', 'read', 'View reports'),
('audit_logs', 'read', 'View audit logs');
