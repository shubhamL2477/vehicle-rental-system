USE vehicle_rental_system;

INSERT INTO roles (id, name, description) VALUES
(1, 'super_admin', 'Can manage the full system'),
(2, 'company', 'Can manage company profile, agents, vehicles, and bookings'),
(3, 'agent', 'Can manage company vehicles and bookings'),
(4, 'user', 'Can browse and book vehicles');

INSERT INTO users (id, role_id, name, email, phone, password, role, status, is_verified, verified_at, address) VALUES
(1, 1, 'System Admin', 'admin@vehiclerental.test', '9800000000', '$2y$10$BoM7rKLIk8v8A5FpQWL6AeE2Du8BzR5Kdfhr9YISQvx5ksQBGY34O', 'super_admin', 'active', 1, '2026-04-01 10:00:00', 'Kathmandu'),
(2, 2, 'Himalayan Wheels Owner', 'company@vehiclerental.test', '9811111111', '$2y$10$nTe/d1zvsa30GmhuxNhxL.l3ywCpWRMUB35lA9ACOrxnhYcKcm3TS', 'company', 'active', 1, '2026-04-01 10:00:00', 'Kathmandu'),
(3, 3, 'Rita Agent', 'agent@vehiclerental.test', '9822222222', '$2y$10$mhpAkpKHkjfYtMEkLZ9SS.Lm.MiXjd4.lw/l/CxKpGBvwAskynLoy', 'agent', 'active', 1, '2026-04-01 10:00:00', 'Kathmandu'),
(4, 4, 'Nabin Renter', 'user@vehiclerental.test', '9833333333', '$2y$10$JxFV/E8zRnQz5QHltJ0r1.SuMKLEEUeT2HdV5ikcEQryT3Bk43U0a', 'user', 'active', 1, '2026-04-01 10:00:00', 'Pokhara');

INSERT INTO companies (id, owner_user_id, name, description, address, contact_email, contact_phone, status) VALUES
(1, 2, 'Himalayan Wheels', 'A local fleet provider for city rides, hill routes, and longer tours across Nepal.', 'Baluwatar, Kathmandu', 'company@vehiclerental.test', '9811111111', 'approved');

INSERT INTO agents (id, user_id, company_id, status, notes) VALUES
(1, 3, 1, 'active', 'Handles booking review, vehicle updates, and day-to-day operations.');

INSERT INTO locations (name, slug, latitude, longitude) VALUES
('Kathmandu Airport', 'kathmandu-airport', 27.6966000, 85.3591000),
('Thamel', 'thamel', 27.7172000, 85.3123000),
('Pokhara Lakeside', 'pokhara-lakeside', 28.2096000, 83.9593000),
('Chitwan Sauraha', 'chitwan-sauraha', 27.5782000, 84.4962000);

INSERT INTO vehicles (id, company_id, name, type, description, price_per_day, driver_price_per_day, seating_capacity, transmission, fuel_type, location, latitude, longitude, status, created_by_user_id) VALUES
(1, 1, 'Suzuki Swift City Drive', 'car', 'Compact city car for local rides and short tours.', 4500.00, 1500.00, 4, 'Automatic', 'Petrol', 'Kathmandu', 27.7172000, 85.3240000, 'available', 2),
(2, 1, 'Toyota Hiace Tour Van', 'van', 'Comfortable multi-day tour van with luggage space.', 9500.00, 2200.00, 10, 'Manual', 'Diesel', 'Pokhara', 28.2096000, 83.9856000, 'available', 3),
(3, 1, 'Royal Enfield Himalayan', 'bike', 'Adventure bike suited for scenic highway and hill routes.', 3000.00, 0.00, 2, 'Manual', 'Petrol', 'Kathmandu', 27.7008000, 85.3333000, 'maintenance', 3);

INSERT INTO availability_blocks (vehicle_id, company_id, start_datetime, end_datetime, reason, created_by_user_id) VALUES
(3, 1, '2026-04-10 09:00:00', '2026-04-15 18:00:00', 'Scheduled service maintenance', 3);

INSERT INTO bookings (id, user_id, vehicle_id, company_id, agent_id, start_datetime, end_datetime, pickup_location, destination, with_driver, total_price, status, terms_accepted, payment_method, payment_status, notes) VALUES
(1, 4, 1, 1, 1, '2026-04-18 09:00:00', '2026-04-20 18:00:00', 'Kathmandu Airport', 'Pokhara', 1, 12000.00, 'pending', 1, 'cash', 'cash_due', 'Awaiting agent review'),
(2, 4, 2, 1, 1, '2026-04-25 08:00:00', '2026-04-28 18:00:00', 'Pokhara Lakeside', 'Chitwan Sauraha', 1, 35100.00, 'confirmed', 1, 'cash', 'cash_due', 'Approved for a multi-day family tour');

INSERT INTO booking_documents (booking_id, document_type, file_name, original_name) VALUES
(1, 'license', 'sample-license.pdf', 'sample-license.pdf'),
(1, 'passport', 'sample-passport.pdf', 'sample-passport.pdf'),
(2, 'license', 'sample-license-2.pdf', 'sample-license-2.pdf'),
(2, 'citizenship', 'sample-citizenship.pdf', 'sample-citizenship.pdf');
