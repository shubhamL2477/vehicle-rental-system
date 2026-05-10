USE vehicle_rental;

INSERT INTO roles (id, name) VALUES
(1, 'user'),
(2, 'company'),
(3, 'agent'),
(4, 'admin'),
(5, 'super_admin');

INSERT INTO vehicle_categories (id, name) VALUES
(1, 'Bike'),
(2, 'Car'),
(3, 'SUV'),
(4, 'Van'),
(5, 'Truck');

INSERT INTO vehicle_types (id, category_id, name) VALUES
(1, 1, 'Motorcycle'),
(2, 1, 'Scooter'),
(3, 2, 'Sedan'),
(4, 2, 'Hatchback'),
(5, 2, 'Coupe'),
(6, 3, 'Compact SUV'),
(7, 3, 'Full-size SUV'),
(8, 4, 'Mini Van'),
(9, 4, 'Passenger Van'),
(10, 5, 'Pickup Truck'),
(11, 5, 'Cargo Truck');

-- Password for all demo accounts: password123
INSERT INTO users
(id, role_id, company_id, name, email, phone, password, company_name, address, status, is_verified)
VALUES
(4, 4, NULL, 'System Admin', 'admin@test.com', '9800000004',
'$2y$10$SiLCGab7d3kJ7GSmNXvP.OJMI1.SXVVZbOOqq7qi/VDgEEF9C3Y6u',
NULL, 'Kathmandu', 'active', 1);

INSERT INTO users
(id, role_id, company_id, name, email, phone, password, company_name, address, status, is_verified)
VALUES
(1, 1, NULL, 'Demo User', 'user@test.com', '9800000001',
'$2y$10$SiLCGab7d3kJ7GSmNXvP.OJMI1.SXVVZbOOqq7qi/VDgEEF9C3Y6u',
NULL, 'Kathmandu', 'active', 1);

INSERT INTO users
(id, role_id, company_id, name, email, phone, password, company_name, address, status, is_verified)
VALUES
(2, 2, NULL, 'Hyrox Rental Owner', 'company@test.com', '9800000002',
'$2y$10$SiLCGab7d3kJ7GSmNXvP.OJMI1.SXVVZbOOqq7qi/VDgEEF9C3Y6u',
'Hyrox Rental', 'Lalitpur', 'active', 1);

INSERT INTO users
(id, role_id, company_id, name, email, phone, password, company_name, address, status, is_verified)
VALUES
(3, 3, 2, 'Asha Agent', 'agent@test.com', '9800000003',
'$2y$10$SiLCGab7d3kJ7GSmNXvP.OJMI1.SXVVZbOOqq7qi/VDgEEF9C3Y6u',
NULL, 'Lalitpur', 'active', 1);

INSERT INTO vehicles
(id, company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
VALUES
(1, 2, 2, 4, 'Suzuki Swift', 'Kathmandu', 4500.00, 6000.00, 'Small car for city travel.', 'available', 27.7172000, 85.3240000, NULL),
(2, 2, 4, 9, 'Toyota Hiace', 'Pokhara', 9000.00, 11500.00, 'Van for group travel.', 'available', 27.7008000, 85.3333000, NULL),
(3, 2, 1, 1, 'Royal Enfield Himalayan', 'Lalitpur', 3000.00, 4500.00, 'Bike for short tours.', 'unavailable', 27.7081000, 85.3296000, NULL);

INSERT INTO maintenance (vehicle_id, start_date, end_date, reason)
VALUES
(3, '2026-05-10', '2026-05-12', 'Basic service and oil change');

INSERT INTO availability_blocks (vehicle_id, company_id, start_datetime, end_datetime, reason, created_by_user_id)
VALUES
(3, 2, '2026-05-10 00:00:00', '2026-05-12 23:59:59', 'Basic service and oil change', 3);

INSERT INTO maintenance_records
(vehicle_id, company_id, title, description, cost, start_datetime, end_datetime, status, availability_block_id, created_by_user_id)
VALUES
(3, 2, 'Basic service and oil change', 'Seed maintenance record linked to blocked availability.', 0.00, '2026-05-10 00:00:00', '2026-05-12 23:59:59', 'scheduled', 1, 3);
