CREATE DATABASE IF NOT EXISTS vehicle_rental
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE vehicle_rental;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS site_reviews;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS maintenance;
DROP TABLE IF EXISTS maintenance_records;
DROP TABLE IF EXISTS availability_blocks;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS vehicle_types;
DROP TABLE IF EXISTS vehicle_categories;
DROP TABLE IF EXISTS company_requests;
DROP TABLE IF EXISTS refresh_tokens;
DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    company_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    company_name VARCHAR(120) NULL,
    address VARCHAR(180) NULL,
    status ENUM('pending', 'pending_admin', 'active', 'rejected', 'inactive') NOT NULL DEFAULT 'pending',
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('verify', 'reset') NOT NULL DEFAULT 'verify',
    expires_at DATETIME NOT NULL,
    last_sent_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE refresh_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    is_revoked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE company_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    request_type ENUM('create', 'update', 'delete') NOT NULL,
    requested_data TEXT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    admin_id INT NULL,
    admin_note VARCHAR(200) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE vehicle_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE vehicle_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(80) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(id) ON DELETE CASCADE
);

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    category_id INT NOT NULL,
    type_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    location VARCHAR(120) NOT NULL,
    self_drive_price DECIMAL(10,2) NOT NULL,
    with_driver_price DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    image VARCHAR(180) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(id),
    FOREIGN KEY (type_id) REFERENCES vehicle_types(id)
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    company_id INT NOT NULL,
    agent_id INT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    pickup_location VARCHAR(150) NOT NULL DEFAULT '',
    destination VARCHAR(150) NOT NULL DEFAULT '',
    with_driver TINYINT(1) NOT NULL DEFAULT 0,
    daily_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    document_file VARCHAR(180) NULL,
    license_file VARCHAR(180) NULL,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    terms_accepted TINYINT(1) NOT NULL DEFAULT 1,
    payment_method ENUM('cash', 'stripe') NOT NULL DEFAULT 'cash',
    payment_status ENUM('cash_due', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'cash_due',
    stripe_session_id VARCHAR(255) NULL,
    stripe_payment_intent_id VARCHAR(255) NULL,
    notes TEXT NULL,
    agent_note VARCHAR(200) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(180) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

CREATE TABLE availability_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    company_id INT NOT NULL DEFAULT 0,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    reason VARCHAR(255) NULL,
    created_by_user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('booking', 'payment', 'reminder', 'review', 'system', 'info') NOT NULL DEFAULT 'info',
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_read_created (user_id, read_at, created_at)
);

CREATE TABLE site_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT NOT NULL,
    feedback TEXT NULL,
    status ENUM('published', 'hidden') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_site_reviews_user (user_id),
    INDEX idx_site_reviews_status (status, rating)
);

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT NULL,
    status ENUM('pending', 'published', 'hidden') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reviews_vehicle_status (vehicle_id, status),
    INDEX idx_reviews_booking (booking_id)
);

CREATE TABLE maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    company_id INT NOT NULL DEFAULT 0,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    availability_block_id INT NULL,
    created_by_user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (availability_block_id) REFERENCES availability_blocks(id) ON DELETE SET NULL
);

CREATE INDEX idx_vehicle_search ON vehicles(name, location, status);
CREATE INDEX idx_booking_status ON bookings(company_id, status);
CREATE INDEX idx_bookings_payment_status ON bookings(payment_status);
CREATE INDEX idx_bookings_stripe_session ON bookings(stripe_session_id);
CREATE INDEX idx_bookings_stripe_payment_intent ON bookings(stripe_payment_intent_id);
CREATE INDEX idx_bookings_vehicle_dates ON bookings(vehicle_id, start_datetime, end_datetime);
CREATE INDEX idx_availability_vehicle_dates ON availability_blocks(vehicle_id, start_datetime, end_datetime);
CREATE INDEX idx_maintenance_vehicle_days ON maintenance_records(vehicle_id, start_date, end_date);
CREATE INDEX idx_maintenance_vehicle_dates ON maintenance_records(vehicle_id, start_datetime, end_datetime);
