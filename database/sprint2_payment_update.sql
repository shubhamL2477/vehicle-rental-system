USE vehicle_rental;

ALTER TABLE bookings
    MODIFY status ENUM('pending', 'confirmed', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS payment_method ENUM('cash', 'stripe') NOT NULL DEFAULT 'cash',
    ADD COLUMN IF NOT EXISTS payment_status ENUM('cash_due', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'cash_due',
    ADD COLUMN IF NOT EXISTS stripe_session_id VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS stripe_payment_intent_id VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    provider ENUM('khalti', 'esewa') NOT NULL DEFAULT 'khalti',
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    gateway_reference VARCHAR(120) NULL,
    webhook_signature VARCHAR(128) NULL,
    webhook_verified TINYINT(1) NOT NULL DEFAULT 0,
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_payments_booking ON payments(booking_id, status);
CREATE INDEX idx_payments_user ON payments(user_id, status);
