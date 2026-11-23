-- AutoCare database schema and sample data (MySQL 8 / MariaDB 10.5+)
-- Import into an empty database:
--   mysql -u root -p -e "CREATE DATABASE autocare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   mysql -u root -p autocare < backend/database/autocare.sql
--
-- Test accounts:  admin@autocare.test / Admin123!   (role admin)
--                 user@autocare.test  / User1234!   (role user)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reminders, fuel_logs, service_records, service_types, vehicles, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehicles (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  make            VARCHAR(50) NOT NULL,
  model           VARCHAR(50) NOT NULL,
  year            SMALLINT UNSIGNED NOT NULL,
  vin             CHAR(17) NOT NULL,
  plate           VARCHAR(20) NOT NULL,
  current_mileage INT UNSIGNED NOT NULL DEFAULT 0,
  fuel_type       ENUM('petrol','diesel','hybrid','electric','lpg') NOT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vehicles_vin (vin),
  KEY idx_vehicles_user (user_id),
  CONSTRAINT fk_vehicles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_types (
  id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                     VARCHAR(100) NOT NULL,
  description              TEXT NULL,
  default_interval_km      INT UNSIGNED NULL,
  default_interval_months  SMALLINT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_service_types_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_records (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  vehicle_id      INT UNSIGNED NOT NULL,
  service_type_id INT UNSIGNED NOT NULL,
  service_date    DATE NOT NULL,
  mileage         INT UNSIGNED NOT NULL,
  cost            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  workshop        VARCHAR(100) NULL,
  notes           TEXT NULL,
  is_repair       TINYINT(1) NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_records_vehicle (vehicle_id),
  KEY idx_records_type (service_type_id),
  CONSTRAINT fk_records_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE,
  CONSTRAINT fk_records_type FOREIGN KEY (service_type_id) REFERENCES service_types (id) ON DELETE RESTRICT,
  CONSTRAINT chk_records_cost CHECK (cost >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fuel_logs (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  vehicle_id      INT UNSIGNED NOT NULL,
  log_date        DATE NOT NULL,
  liters          DECIMAL(7,2) NOT NULL,
  price_per_liter DECIMAL(6,3) NOT NULL,
  total_cost      DECIMAL(10,2) NOT NULL,
  mileage         INT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_fuel_vehicle (vehicle_id),
  CONSTRAINT fk_fuel_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE,
  CONSTRAINT chk_fuel_liters CHECK (liters > 0),
  CONSTRAINT chk_fuel_price CHECK (price_per_liter > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reminders (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  vehicle_id      INT UNSIGNED NOT NULL,
  service_type_id INT UNSIGNED NOT NULL,
  due_date        DATE NULL,
  due_mileage     INT UNSIGNED NULL,
  status          ENUM('pending','done','overdue') NOT NULL DEFAULT 'pending',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reminders_vehicle (vehicle_id),
  KEY idx_reminders_type (service_type_id),
  CONSTRAINT fk_reminders_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE,
  CONSTRAINT fk_reminders_type FOREIGN KEY (service_type_id) REFERENCES service_types (id) ON DELETE RESTRICT,
  CONSTRAINT chk_reminders_target CHECK (due_date IS NOT NULL OR due_mileage IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Sample data
-- ---------------------------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, role) VALUES
  (1, 'Bakir',        'admin@autocare.test', '$2y$10$sZYD8R299Aa9SRZJgSxJ.em0ddtDA4yma7EN6sEkwIZ9Z.JDHCyva', 'admin'),
  (2, 'Amra Hodžić',  'user@autocare.test',  '$2y$10$xxUpETobrPzazbg9MWnVGuOsXUTvcglK7URJ7iXhx5kJ9Pn9y86j.', 'user'),
  (3, 'Tarik Mehić',  'tarik@autocare.test', '$2y$10$xxUpETobrPzazbg9MWnVGuOsXUTvcglK7URJ7iXhx5kJ9Pn9y86j.', 'user');

INSERT INTO vehicles (id, user_id, make, model, year, vin, plate, current_mileage, fuel_type) VALUES
  (1, 2, 'BMW',   'M340i',   2021, 'WBA5R1C50KFH12345', 'A12-K-345', 48320,  'petrol'),
  (2, 2, 'Mazda', 'MX-5',    2018, 'JM1NDAD75J0212345', 'J45-E-908', 62900,  'petrol'),
  (3, 3, 'Škoda', 'Octavia', 2016, 'TMBJG7NE4G0123456', 'M78-J-112', 148450, 'diesel'),
  (4, 1, 'VW',    'Golf',    2019, 'WVWZZZAUZGW123456', 'E33-A-210', 71200,  'diesel');

INSERT INTO service_types (id, name, description, default_interval_km, default_interval_months) VALUES
  (1, 'Oil change',        'Engine oil and filter',                          15000, 12),
  (2, 'Brake pads',        'Front and rear pad inspection or replacement',   40000, 24),
  (3, 'Tyre rotation',     'Rotate and balance all four tyres',              10000, 6),
  (4, 'Air filter',        'Engine air filter replacement',                  30000, 24),
  (5, 'Brake fluid',       'Brake fluid flush',                              NULL,  24),
  (6, 'Timing belt',       'Timing belt and water pump',                     100000, 60),
  (7, 'Annual inspection', 'Mandatory yearly technical inspection',          NULL,  12),
  (8, 'Clutch',            'Clutch kit replacement',                         NULL,  NULL);

INSERT INTO service_records (id, vehicle_id, service_type_id, service_date, mileage, cost, workshop, notes, is_repair) VALUES
  (1, 1, 1, '2026-04-03', 40100, 145.00, 'Auto Servis Mostar', 'Synthetic 5W-30',        0),
  (2, 1, 5, '2026-01-15', 36800,  60.00, 'Auto Servis Mostar', NULL,                     0),
  (3, 1, 3, '2025-10-02', 31500,  30.00, 'Gume Plus',          NULL,                     0),
  (4, 2, 8, '2026-02-21', 58300, 820.00, 'MX Garage',          'Clutch worn out',        1),
  (5, 2, 1, '2026-06-20', 60000, 110.00, 'MX Garage',          NULL,                     0),
  (6, 3, 4, '2026-10-18', 148200, 72.50, 'Servis Tuzla',       NULL,                     0),
  (7, 3, 2, '2024-09-30', 120100, 260.00, 'Servis Tuzla',      'Front pads and discs',   0),
  (8, 4, 7, '2026-03-12', 69800,  85.00, 'Tehnički pregled',   NULL,                     0);

INSERT INTO fuel_logs (id, vehicle_id, log_date, liters, price_per_liter, total_cost, mileage) VALUES
  (1,  1, '2026-10-22', 42.10, 1.898, 79.90, 48320),
  (2,  1, '2026-10-09', 38.70, 1.890, 73.14, 47610),
  (3,  1, '2026-09-24', 40.20, 1.875, 75.38, 46850),
  (4,  2, '2026-10-14', 31.60, 1.965, 62.09, 62900),
  (5,  2, '2026-09-28', 29.40, 1.950, 57.33, 62380),
  (6,  3, '2026-10-20', 45.00, 1.720, 77.40, 148450),
  (7,  3, '2026-10-02', 47.30, 1.715, 81.12, 147700),
  (8,  4, '2026-10-11', 44.80, 1.730, 77.50, 71200),
  (9,  4, '2026-09-19', 43.20, 1.725, 74.52, 70520),
  (10, 1, '2026-09-05', 41.00, 1.880, 77.08, 46100);

INSERT INTO reminders (id, vehicle_id, service_type_id, due_date, due_mileage, status) VALUES
  (1, 3, 2, '2026-10-12', 148000, 'overdue'),
  (2, 1, 1, '2026-11-30',  50000, 'pending'),
  (3, 2, 3, '2026-11-15',  63500, 'pending'),
  (4, 2, 1, '2026-06-20',  60000, 'done'),
  (5, 4, 7, '2027-03-12',   NULL, 'pending'),
  (6, 1, 3, NULL,          55000, 'pending');
