USE vehicle_rental;

ALTER TABLE bookings
    MODIFY status ENUM('pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending';

INSERT INTO vehicle_categories (id, name) VALUES
(1, 'Bike'),
(2, 'Car'),
(3, 'SUV'),
(4, 'Bus'),
(5, 'Truck'),
(6, 'Other')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO vehicle_types (id, category_id, name) VALUES
(1, 1, 'Motorcycle'),
(2, 1, 'Scooter'),
(3, 2, 'Sedan'),
(4, 2, 'Hatchback'),
(5, 2, 'Coupe'),
(6, 3, 'Compact SUV'),
(7, 3, 'Full-size SUV'),
(8, 4, 'Mini Bus'),
(9, 4, 'Tourist Bus'),
(10, 5, 'Pickup Truck'),
(11, 5, 'Cargo Truck'),
(12, 6, 'Electric Rickshaw'),
(13, 6, 'Micro EV')
ON DUPLICATE KEY UPDATE category_id = VALUES(category_id), name = VALUES(name);

INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 1, 1, 'Royal Enfield Classic 350', 'Kathmandu', 2800.00, 4200.00, 'Comfortable motorcycle for city and highway rides.', 'available', 27.7172000, 85.3240000, '0e4301da5051a2bdb16b1e60ee751514.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Royal Enfield Classic 350');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 1, 1, 'Yamaha FZ V3', 'Lalitpur', 2200.00, 3600.00, 'Light motorcycle for daily rental.', 'available', 27.6710000, 85.3188000, '055053a78666299f2b4f8543fa094725.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Yamaha FZ V3');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 1, 2, 'Honda Dio Scooter', 'Bhaktapur', 1500.00, 2800.00, 'Easy scooter for city travel.', 'available', 27.6722000, 85.4278000, '4be81165f83c1cc56fabfcaf4315772f.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Honda Dio Scooter');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 1, 2, 'TVS Ntorq 125', 'Pokhara', 1700.00, 3000.00, 'Sporty scooter for short trips.', 'available', 28.2096000, 83.9856000, '885ad27baae6745c618604a9601520b8.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'TVS Ntorq 125');

INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 2, 3, 'Hyundai Verna', 'Kathmandu', 5200.00, 6800.00, 'Sedan for family and business travel.', 'available', 27.7172000, 85.3240000, 'c09b6b913cc1c1e0d42154695e6c1c52.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Hyundai Verna');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 2, 4, 'Hyundai i20', 'Lalitpur', 4300.00, 5900.00, 'Premium hatchback for city use.', 'available', 27.6710000, 85.3188000, 'f2f04e658282814f530e6950ed1b7dbb.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Hyundai i20');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 2, 3, 'Honda City', 'Pokhara', 5600.00, 7200.00, 'Reliable sedan with comfortable seating.', 'available', 28.2096000, 83.9856000, 'file_69f8469c4632b5.08052520.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Honda City');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 2, 4, 'Tata Tiago', 'Chitwan', 3900.00, 5400.00, 'Budget hatchback for local rentals.', 'available', 27.5291000, 84.3542000, 'file_69f846d9666933.83186250.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Tata Tiago');

INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 3, 6, 'Hyundai Creta', 'Kathmandu', 7500.00, 9400.00, 'Compact SUV for city and highway routes.', 'available', 27.7172000, 85.3240000, 'file_69f846e5b935e9.76579238.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Hyundai Creta');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 3, 6, 'Kia Seltos', 'Lalitpur', 7800.00, 9700.00, 'Modern SUV with strong comfort features.', 'available', 27.6710000, 85.3188000, '0e4301da5051a2bdb16b1e60ee751514.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Kia Seltos');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 3, 7, 'Toyota Fortuner', 'Pokhara', 12500.00, 15000.00, 'Full-size SUV for group tours.', 'available', 28.2096000, 83.9856000, '055053a78666299f2b4f8543fa094725.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Toyota Fortuner');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 3, 7, 'Mahindra Scorpio', 'Butwal', 9000.00, 11200.00, 'Strong SUV for mixed road conditions.', 'available', 27.7006000, 83.4484000, '4be81165f83c1cc56fabfcaf4315772f.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Mahindra Scorpio');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 3, 6, 'Nissan Kicks', 'Dharan', 7200.00, 9000.00, 'Compact SUV for city and hill trips.', 'available', 26.8125000, 87.2833000, '885ad27baae6745c618604a9601520b8.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Nissan Kicks');

INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 4, 8, 'Tata Winger Mini Bus', 'Kathmandu', 9500.00, 11800.00, 'Mini bus for office and family groups.', 'available', 27.7172000, 85.3240000, 'c09b6b913cc1c1e0d42154695e6c1c52.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Tata Winger Mini Bus');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 4, 9, 'Ashok Leyland Tourist Bus', 'Pokhara', 18500.00, 22000.00, 'Tourist bus for long group routes.', 'available', 28.2096000, 83.9856000, 'f2f04e658282814f530e6950ed1b7dbb.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Ashok Leyland Tourist Bus');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 4, 8, 'Eicher City Bus', 'Biratnagar', 14500.00, 17800.00, 'City bus for local group movement.', 'available', 26.4525000, 87.2718000, 'file_69f8469c4632b5.08052520.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Eicher City Bus');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 4, 9, 'Hino Luxury Coach', 'Chitwan', 21000.00, 24800.00, 'Luxury coach for tourist groups.', 'available', 27.5291000, 84.3542000, 'file_69f846d9666933.83186250.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Hino Luxury Coach');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 4, 8, 'Force Traveller Bus', 'Lalitpur', 11200.00, 13800.00, 'Comfortable mini bus for medium groups.', 'available', 27.6710000, 85.3188000, 'file_69f846e5b935e9.76579238.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Force Traveller Bus');

INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 5, 10, 'Mahindra Bolero Pickup', 'Kathmandu', 6200.00, 8000.00, 'Pickup truck for light cargo.', 'available', 27.7172000, 85.3240000, '0e4301da5051a2bdb16b1e60ee751514.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Mahindra Bolero Pickup');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 5, 10, 'Tata Xenon Pickup', 'Pokhara', 6800.00, 8600.00, 'Pickup for mixed personal and cargo use.', 'available', 28.2096000, 83.9856000, '055053a78666299f2b4f8543fa094725.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Tata Xenon Pickup');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 5, 11, 'Isuzu Cargo Truck', 'Birgunj', 13500.00, 16500.00, 'Cargo truck for commercial rentals.', 'available', 27.0104000, 84.8774000, '4be81165f83c1cc56fabfcaf4315772f.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Isuzu Cargo Truck');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 5, 11, 'Eicher Pro Cargo', 'Butwal', 12800.00, 15600.00, 'Medium cargo vehicle for business deliveries.', 'available', 27.7006000, 83.4484000, '885ad27baae6745c618604a9601520b8.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Eicher Pro Cargo');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 5, 10, 'Toyota Hilux Pickup', 'Dharan', 9800.00, 12200.00, 'Premium pickup for rugged routes.', 'available', 26.8125000, 87.2833000, 'c09b6b913cc1c1e0d42154695e6c1c52.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Toyota Hilux Pickup');

INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 6, 12, 'Safa Tempo Electric', 'Kathmandu', 1800.00, 2800.00, 'Electric three-wheeler for short local trips.', 'available', 27.7172000, 85.3240000, 'f2f04e658282814f530e6950ed1b7dbb.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Safa Tempo Electric');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 6, 13, 'Wuling Air EV', 'Lalitpur', 3600.00, 5000.00, 'Small EV for clean city travel.', 'available', 27.6710000, 85.3188000, 'file_69f8469c4632b5.08052520.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Wuling Air EV');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 6, 13, 'MG Comet EV', 'Pokhara', 3900.00, 5400.00, 'Compact EV for city sightseeing.', 'available', 28.2096000, 83.9856000, 'file_69f846d9666933.83186250.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'MG Comet EV');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 6, 12, 'Bajaj RE Auto', 'Chitwan', 1400.00, 2400.00, 'Auto rickshaw for local rental use.', 'available', 27.5291000, 84.3542000, 'file_69f846e5b935e9.76579238.jpg'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Bajaj RE Auto');
INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
SELECT 2, 6, 13, 'Citroen eC3', 'Biratnagar', 5200.00, 6800.00, 'Electric city car listed under other category.', 'available', 26.4525000, 87.2718000, '0e4301da5051a2bdb16b1e60ee751514.webp'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE name = 'Citroen eC3');
