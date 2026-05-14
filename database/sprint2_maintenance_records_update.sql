USE vehicle_rental;

CREATE TABLE IF NOT EXISTS maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    company_id INT NOT NULL DEFAULT 0,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    start_date DATE NULL,
    end_date DATE NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    availability_block_id INT NULL,
    created_by_user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE maintenance_records
    ADD COLUMN IF NOT EXISTS start_date DATE NULL AFTER cost,
    ADD COLUMN IF NOT EXISTS end_date DATE NULL AFTER start_date;

UPDATE maintenance_records
SET start_date = DATE(start_datetime)
WHERE start_date IS NULL;

UPDATE maintenance_records
SET end_date = DATE(end_datetime)
WHERE end_date IS NULL;

ALTER TABLE maintenance_records
    MODIFY COLUMN start_date DATE NOT NULL,
    MODIFY COLUMN end_date DATE NOT NULL;

CREATE INDEX IF NOT EXISTS idx_maintenance_vehicle_days ON maintenance_records(vehicle_id, start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_maintenance_vehicle_dates ON maintenance_records(vehicle_id, start_datetime, end_datetime);
