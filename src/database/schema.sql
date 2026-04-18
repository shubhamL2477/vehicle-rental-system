CREATE DATABASE IF NOT EXISTS vehicle_rental_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vehicle_rental_system;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS booking_documents;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS availability_blocks;
DROP TABLE IF EXISTS vehicle_images;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS agents;
DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'company', 'agent', 'user') NOT NULL,
    status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verified_at DATETIME NULL,
    address VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE otp_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('account_verification', 'password_reset') NOT NULL DEFAULT 'account_verification',
    otp_expires_at DATETIME NULL,
    otp_last_sent_at DATETIME NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT UNSIGNED NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    address VARCHAR(255) NOT NULL,
    contact_email VARCHAR(120) NOT NULL,
    contact_phone VARCHAR(30) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_companies_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(150) NOT NULL UNIQUE,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE agents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    company_id INT UNSIGNED NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_agents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_agents_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    type ENUM('car', 'bike', 'bus', 'van', 'jeep', 'suv', 'scooter') NOT NULL,
    description TEXT NULL,
    price_per_day DECIMAL(10, 2) NOT NULL,
    driver_price_per_day DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    seating_capacity INT NOT NULL DEFAULT 4,
    transmission VARCHAR(50) NULL,
    fuel_type VARCHAR(50) NULL,
    location VARCHAR(150) NOT NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    status ENUM('available', 'unavailable', 'maintenance') NOT NULL DEFAULT 'available',
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicles_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicles_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE vehicle_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicle_images_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

CREATE TABLE availability_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    reason VARCHAR(255) NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_availability_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    CONSTRAINT fk_availability_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_availability_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    agent_id INT UNSIGNED NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    pickup_location VARCHAR(150) NOT NULL,
    destination VARCHAR(150) NOT NULL,
    with_driver TINYINT(1) NOT NULL DEFAULT 0,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    terms_accepted TINYINT(1) NOT NULL DEFAULT 1,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'cash_due',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_agent FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE SET NULL
);

CREATE TABLE booking_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    document_type ENUM('license', 'passport', 'citizenship') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_documents_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE INDEX idx_bookings_vehicle_dates ON bookings(vehicle_id, start_datetime, end_datetime);
CREATE INDEX idx_bookings_company_status ON bookings(company_id, status);
CREATE INDEX idx_availability_vehicle_dates ON availability_blocks(vehicle_id, start_datetime, end_datetime);
