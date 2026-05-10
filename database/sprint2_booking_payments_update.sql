USE vehicle_rental;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS company_name VARCHAR(120) NULL AFTER password;

UPDATE users
SET company_name = name
WHERE (company_name IS NULL OR company_name = '')
  AND (role = 'company' OR role_id IN (SELECT id FROM roles WHERE name = 'company'));

CREATE TABLE IF NOT EXISTS availability_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    company_id INT NOT NULL DEFAULT 0,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    reason VARCHAR(255) NULL,
    created_by_user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS start_datetime DATETIME NULL AFTER agent_id,
    ADD COLUMN IF NOT EXISTS end_datetime DATETIME NULL AFTER start_datetime,
    ADD COLUMN IF NOT EXISTS pickup_location VARCHAR(150) NOT NULL DEFAULT '' AFTER end_datetime,
    ADD COLUMN IF NOT EXISTS destination VARCHAR(150) NOT NULL DEFAULT '' AFTER pickup_location,
    ADD COLUMN IF NOT EXISTS terms_accepted TINYINT(1) NOT NULL DEFAULT 1 AFTER status,
    ADD COLUMN IF NOT EXISTS payment_method ENUM('cash', 'stripe') NOT NULL DEFAULT 'cash' AFTER terms_accepted,
    ADD COLUMN IF NOT EXISTS payment_status ENUM('cash_due', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'cash_due' AFTER payment_method,
    ADD COLUMN IF NOT EXISTS stripe_session_id VARCHAR(255) NULL AFTER payment_status,
    ADD COLUMN IF NOT EXISTS stripe_payment_intent_id VARCHAR(255) NULL AFTER stripe_session_id,
    ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER stripe_payment_intent_id,
    MODIFY COLUMN IF EXISTS start_date DATE NULL,
    MODIFY COLUMN IF EXISTS end_date DATE NULL,
    MODIFY COLUMN status ENUM('pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    MODIFY COLUMN payment_method ENUM('cash', 'stripe') NOT NULL DEFAULT 'cash',
    MODIFY COLUMN payment_status ENUM('cash_due', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'cash_due';

UPDATE bookings
SET start_datetime = TIMESTAMP(start_date, '00:00:00')
WHERE start_datetime IS NULL
  AND start_date IS NOT NULL;

UPDATE bookings
SET end_datetime = TIMESTAMP(end_date, '23:59:59')
WHERE end_datetime IS NULL
  AND end_date IS NOT NULL;

ALTER TABLE bookings
    MODIFY COLUMN start_datetime DATETIME NOT NULL,
    MODIFY COLUMN end_datetime DATETIME NOT NULL;

CREATE INDEX IF NOT EXISTS idx_bookings_payment_status ON bookings(payment_status);
CREATE INDEX IF NOT EXISTS idx_bookings_stripe_session ON bookings(stripe_session_id);
CREATE INDEX IF NOT EXISTS idx_bookings_stripe_payment_intent ON bookings(stripe_payment_intent_id);
CREATE INDEX IF NOT EXISTS idx_bookings_vehicle_dates ON bookings(vehicle_id, start_datetime, end_datetime);
CREATE INDEX IF NOT EXISTS idx_availability_vehicle_dates ON availability_blocks(vehicle_id, start_datetime, end_datetime);

CREATE TABLE IF NOT EXISTS maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    company_id INT NOT NULL DEFAULT 0,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    availability_block_id INT NULL,
    created_by_user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_maintenance_vehicle_dates ON maintenance_records(vehicle_id, start_datetime, end_datetime);
