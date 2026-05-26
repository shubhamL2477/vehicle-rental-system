USE vehicle_rental;

-- Sample login password for all seed accounts: password123
SET @demo_password = '$2y$10$SiLCGab7d3kJ7GSmNXvP.OJMI1.SXVVZbOOqq7qi/VDgEEF9C3Y6u';

INSERT INTO roles (id, name, role_name) VALUES
(1, 'super_admin', 'super_admin'),
(2, 'company', 'company'),
(3, 'agent', 'agent'),
(4, 'user', 'user')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

INSERT INTO vehicle_categories (id, name) VALUES
(1, 'Bike'),
(2, 'Car'),
(3, 'Bus'),
(4, 'Van'),
(5, 'Jeep'),
(6, 'SUV'),
(7, 'Scooter')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO vehicle_types (id, category_id, name) VALUES
(1, 1, 'Bike'),
(2, 3, 'Bus'),
(3, 2, 'Car'),
(4, 5, 'Jeep'),
(5, 7, 'Scooter'),
(6, 6, 'SUV'),
(7, 4, 'Van')
ON DUPLICATE KEY UPDATE category_id = VALUES(category_id), name = VALUES(name);

-- Keep the familiar demo accounts active.
UPDATE users SET status = 'active', is_verified = 1 WHERE email IN ('admin@test.com', 'user@test.com', 'company@test.com', 'agent@test.com');

INSERT INTO users
(id, role_id, company_id, name, email, phone, password, company_name, status, is_verified, address)
VALUES
(100, 1, NULL, 'System Admin', 'admin.seed@test.com', '9801000100', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(101, 4, NULL, 'Demo User 01', 'user01@test.com', '9801000101', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(102, 4, NULL, 'Demo User 02', 'user02@test.com', '9801000102', @demo_password, NULL, 'active', 1, 'Lalitpur'),
(103, 4, NULL, 'Demo User 03', 'user03@test.com', '9801000103', @demo_password, NULL, 'active', 1, 'Bhaktapur'),
(104, 4, NULL, 'Demo User 04', 'user04@test.com', '9801000104', @demo_password, NULL, 'active', 1, 'Pokhara'),
(105, 4, NULL, 'Demo User 05', 'user05@test.com', '9801000105', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(106, 4, NULL, 'Demo User 06', 'user06@test.com', '9801000106', @demo_password, NULL, 'active', 1, 'Lalitpur'),
(107, 4, NULL, 'Demo User 07', 'user07@test.com', '9801000107', @demo_password, NULL, 'active', 1, 'Bhaktapur'),
(108, 4, NULL, 'Demo User 08', 'user08@test.com', '9801000108', @demo_password, NULL, 'active', 1, 'Pokhara'),
(109, 4, NULL, 'Demo User 09', 'user09@test.com', '9801000109', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(110, 4, NULL, 'Demo User 10', 'user10@test.com', '9801000110', @demo_password, NULL, 'active', 1, 'Lalitpur'),
(111, 4, NULL, 'Demo User 11', 'user11@test.com', '9801000111', @demo_password, NULL, 'active', 1, 'Bhaktapur'),
(112, 4, NULL, 'Demo User 12', 'user12@test.com', '9801000112', @demo_password, NULL, 'active', 1, 'Pokhara'),
(113, 4, NULL, 'Demo User 13', 'user13@test.com', '9801000113', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(114, 4, NULL, 'Demo User 14', 'user14@test.com', '9801000114', @demo_password, NULL, 'active', 1, 'Lalitpur'),
(115, 4, NULL, 'Demo User 15', 'user15@test.com', '9801000115', @demo_password, NULL, 'active', 1, 'Bhaktapur'),
(117, 2, NULL, 'City Wheels Owner', 'company01@test.com', '9801000117', @demo_password, 'City Wheels Rental', 'active', 1, 'Kathmandu'),
(118, 2, NULL, 'Himalayan Drive Owner', 'company02@test.com', '9801000118', @demo_password, 'Himalayan Drive', 'active', 1, 'Lalitpur'),
(119, 2, NULL, 'Pokhara Ride Owner', 'company03@test.com', '9801000119', @demo_password, 'Pokhara Ride Hub', 'active', 1, 'Pokhara'),
(120, 2, NULL, 'Metro Motors Owner', 'company04@test.com', '9801000120', @demo_password, 'Metro Motors', 'active', 1, 'Bhaktapur'),
(121, 2, NULL, 'Everest Fleet Owner', 'company05@test.com', '9801000121', @demo_password, 'Everest Fleet', 'active', 1, 'Kathmandu'),
(122, 3, 117, 'Agent 01', 'agent01@test.com', '9801000122', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(123, 3, 117, 'Agent 02', 'agent02@test.com', '9801000123', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(124, 3, 117, 'Agent 03', 'agent03@test.com', '9801000124', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(125, 3, 118, 'Agent 04', 'agent04@test.com', '9801000125', @demo_password, NULL, 'active', 1, 'Lalitpur'),
(126, 3, 118, 'Agent 05', 'agent05@test.com', '9801000126', @demo_password, NULL, 'active', 1, 'Lalitpur'),
(127, 3, 118, 'Agent 06', 'agent06@test.com', '9801000127', @demo_password, NULL, 'active', 1, 'Lalitpur'),
(128, 3, 119, 'Agent 07', 'agent07@test.com', '9801000128', @demo_password, NULL, 'active', 1, 'Pokhara'),
(129, 3, 119, 'Agent 08', 'agent08@test.com', '9801000129', @demo_password, NULL, 'active', 1, 'Pokhara'),
(130, 3, 119, 'Agent 09', 'agent09@test.com', '9801000130', @demo_password, NULL, 'active', 1, 'Pokhara'),
(131, 3, 120, 'Agent 10', 'agent10@test.com', '9801000131', @demo_password, NULL, 'active', 1, 'Bhaktapur'),
(132, 3, 120, 'Agent 11', 'agent11@test.com', '9801000132', @demo_password, NULL, 'active', 1, 'Bhaktapur'),
(133, 3, 120, 'Agent 12', 'agent12@test.com', '9801000133', @demo_password, NULL, 'active', 1, 'Bhaktapur'),
(134, 3, 121, 'Agent 13', 'agent13@test.com', '9801000134', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(135, 3, 121, 'Agent 14', 'agent14@test.com', '9801000135', @demo_password, NULL, 'active', 1, 'Kathmandu'),
(136, 3, 121, 'Agent 15', 'agent15@test.com', '9801000136', @demo_password, NULL, 'active', 1, 'Kathmandu')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
company_id = VALUES(company_id),
password = VALUES(password),
company_name = VALUES(company_name),
status = VALUES(status),
is_verified = VALUES(is_verified),
address = VALUES(address);

-- 20 vehicles for each category.
INSERT INTO vehicles
(id, company_id, category_id, type_id, name, description, location, self_drive_price, with_driver_price, latitude, lat, longitude, `long`, image, status, availability)
SELECT
    1000 + ((vc.id - 1) * 20) + n.n AS id,
    117 + MOD(n.n + vc.id, 5) AS company_id,
    vc.id AS category_id,
    vt.id AS type_id,
    CONCAT(vc.name, ' Rental ', LPAD(n.n, 2, '0')) AS name,
    CONCAT('Sample ', vc.name, ' available for rent.') AS description,
    CASE MOD(n.n, 4)
        WHEN 0 THEN 'Kathmandu'
        WHEN 1 THEN 'Lalitpur'
        WHEN 2 THEN 'Bhaktapur'
        ELSE 'Pokhara'
    END AS location,
    CASE vc.name
        WHEN 'Bike' THEN 1200 + (n.n * 50)
        WHEN 'Scooter' THEN 900 + (n.n * 40)
        WHEN 'Car' THEN 3500 + (n.n * 100)
        WHEN 'SUV' THEN 5500 + (n.n * 120)
        WHEN 'Jeep' THEN 5000 + (n.n * 110)
        WHEN 'Van' THEN 6500 + (n.n * 130)
        ELSE 8000 + (n.n * 150)
    END AS self_drive_price,
    CASE vc.name
        WHEN 'Bike' THEN 1800 + (n.n * 50)
        WHEN 'Scooter' THEN 1400 + (n.n * 40)
        WHEN 'Car' THEN 4700 + (n.n * 100)
        WHEN 'SUV' THEN 7000 + (n.n * 120)
        WHEN 'Jeep' THEN 6500 + (n.n * 110)
        WHEN 'Van' THEN 8200 + (n.n * 130)
        ELSE 9800 + (n.n * 150)
    END AS with_driver_price,
    27.7000000 + (n.n / 10000) AS latitude,
    27.7000000 + (n.n / 10000) AS lat,
    85.3000000 + (vc.id / 1000) AS longitude,
    85.3000000 + (vc.id / 1000) AS `long`,
    CASE vc.name
        WHEN 'Bike' THEN CASE MOD(n.n, 2)
            WHEN 0 THEN 'assets/images/Royal Enfield Classic 350.webp'
            ELSE 'assets/images/Yamaha FZ V3.webp'
        END
        WHEN 'Car' THEN CASE MOD(n.n, 9)
            WHEN 0 THEN 'assets/images/Honda City.avif'
            WHEN 1 THEN 'assets/images/Hyundai i20.avif'
            WHEN 2 THEN 'assets/images/Hyundai Verna.avif'
            WHEN 3 THEN 'assets/images/Suzuki Swift.jpg'
            WHEN 4 THEN 'assets/images/Tata Tiago.avif'
            WHEN 5 THEN 'assets/images/citroen ec3.avif'
            WHEN 6 THEN 'assets/images/MG Comet EV.webp'
            WHEN 7 THEN 'assets/images/Wuling Air EV.avif'
            ELSE 'assets/images/Nissan Kicks.avif'
        END
        WHEN 'Bus' THEN CASE MOD(n.n, 4)
            WHEN 0 THEN 'assets/images/Ashok Leyland Tourist Bus.avif'
            WHEN 1 THEN 'assets/images/Eicher City Bus.avif'
            WHEN 2 THEN 'assets/images/Hino Luxury Coach.jpg'
            ELSE 'assets/images/Force Traveller Bus.avif'
        END
        WHEN 'Van' THEN CASE MOD(n.n, 3)
            WHEN 0 THEN 'assets/images/Toyota Hiace.jpg'
            WHEN 1 THEN 'assets/images/Tata Winger Mini Bus.jpg'
            ELSE 'assets/images/Force Traveller Bus.avif'
        END
        WHEN 'Jeep' THEN CASE MOD(n.n, 4)
            WHEN 0 THEN 'assets/images/Mahindra Scorpio.avif'
            WHEN 1 THEN 'assets/images/Toyota Hilux Pickup.jpg'
            WHEN 2 THEN 'assets/images/Tata Xenon Pickup.jpg'
            ELSE 'assets/images/Mahindra Bolero Pickup.avif'
        END
        WHEN 'SUV' THEN CASE MOD(n.n, 4)
            WHEN 0 THEN 'assets/images/Toyota Fortuner.avif'
            WHEN 1 THEN 'assets/images/Hyundai Creta.avif'
            WHEN 2 THEN 'assets/images/Kia Seltos.avif'
            ELSE 'assets/images/Nissan Kicks.avif'
        END
        ELSE CASE MOD(n.n, 3)
            WHEN 0 THEN 'assets/images/Honda Dio Scooter.jpeg'
            WHEN 1 THEN 'assets/images/TVS Ntorq 125.avif'
            ELSE 'assets/images/Safa Tempo Electric.webp'
        END
    END AS image,
    'available' AS status,
    'available' AS availability
FROM vehicle_categories vc
JOIN vehicle_types vt ON vt.category_id = vc.id
JOIN (
    SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
    UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
    UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
) n
WHERE vc.id BETWEEN 1 AND 7
ON DUPLICATE KEY UPDATE
company_id = VALUES(company_id),
category_id = VALUES(category_id),
type_id = VALUES(type_id),
name = VALUES(name),
description = VALUES(description),
location = VALUES(location),
self_drive_price = VALUES(self_drive_price),
with_driver_price = VALUES(with_driver_price),
latitude = VALUES(latitude),
lat = VALUES(lat),
longitude = VALUES(longitude),
`long` = VALUES(`long`),
image = VALUES(image),
status = VALUES(status),
availability = VALUES(availability);
