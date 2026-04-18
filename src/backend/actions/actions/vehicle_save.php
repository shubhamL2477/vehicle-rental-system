<?php

require_once __DIR__ . '/../../../includes/bootstrap.php';

// Company, agent, and admin can save vehicle details.
require_role(['company', 'agent']);
require_csrf();

$companyId = managed_company_id();
$user = current_user();
$vehicleId = isset($_POST['vehicle_id']) ? (int) $_POST['vehicle_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$type = isset($_POST['type']) ? $_POST['type'] : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$pricePerDay = isset($_POST['price_per_day']) ? (float) $_POST['price_per_day'] : 0;
$driverPricePerDay = isset($_POST['driver_price_per_day']) ? (float) $_POST['driver_price_per_day'] : 0;
$seatingCapacity = isset($_POST['seating_capacity']) ? (int) $_POST['seating_capacity'] : 0;
$transmission = isset($_POST['transmission']) ? trim($_POST['transmission']) : '';
$fuelType = isset($_POST['fuel_type']) ? trim($_POST['fuel_type']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
$longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : 'available';

if (
    !$companyId ||
    $name === '' ||
    !in_array($type, VEHICLE_TYPES, true) ||
    $pricePerDay <= 0 ||
    $seatingCapacity < 1 ||
    $location === '' ||
    !in_array($status, VEHICLE_STATUSES, true)
) {
    set_flash('Please complete the vehicle form with valid values.', 'danger');
    redirect('dashboard.php?section=vehicles');
}

$existingVehicle = null;

if ($vehicleId > 0) {
    $existingVehicle = db_one('SELECT * FROM vehicles WHERE id = ? AND company_id = ?', [$vehicleId, $companyId]);

    if (!$existingVehicle) {
        set_flash('Vehicle not found.', 'danger');
        redirect('dashboard.php?section=vehicles');
    }
}

$pdo = require_db();

try {
    $pdo->beginTransaction();

    if ($existingVehicle) {
        // Update old vehicle data.
        $pdo->prepare(
            'UPDATE vehicles
             SET name = ?, type = ?, description = ?, price_per_day = ?, driver_price_per_day = ?, seating_capacity = ?,
                 transmission = ?, fuel_type = ?, location = ?, latitude = ?, longitude = ?, status = ?
             WHERE id = ? AND company_id = ?'
        )->execute([
            $name,
            $type,
            $description,
            $pricePerDay,
            $driverPricePerDay,
            $seatingCapacity,
            $transmission,
            $fuelType,
            $location,
            $latitude !== '' ? $latitude : null,
            $longitude !== '' ? $longitude : null,
            $status,
            $vehicleId,
            $companyId,
        ]);
    } else {
        // Insert new vehicle.
        $pdo->prepare(
            'INSERT INTO vehicles
             (company_id, name, type, description, price_per_day, driver_price_per_day, seating_capacity, transmission, fuel_type, location, latitude, longitude, status, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $companyId,
            $name,
            $type,
            $description,
            $pricePerDay,
            $driverPricePerDay,
            $seatingCapacity,
            $transmission,
            $fuelType,
            $location,
            $latitude !== '' ? $latitude : null,
            $longitude !== '' ? $longitude : null,
            $status,
            (int) ($user['id'] ?? 0),
        ]);

        $vehicleId = (int) $pdo->lastInsertId();
    }

    if (isset($_FILES['vehicle_images'])) {
        $files = save_uploaded_files($_FILES['vehicle_images'], VEHICLE_UPLOAD_DIR, ALLOWED_IMAGE_EXTENSIONS);
        $hasPrimary = (int) db_value('SELECT COUNT(*) FROM vehicle_images WHERE vehicle_id = ? AND is_primary = 1', [$vehicleId]) > 0;

        foreach ($files as $index => $file) {
            $pdo->prepare(
                'INSERT INTO vehicle_images (vehicle_id, file_name, original_name, is_primary)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $vehicleId,
                $file['generated_name'],
                $file['original_name'],
                $hasPrimary ? 0 : ($index === 0 ? 1 : 0),
            ]);
        }
    }

    $pdo->commit();
    set_flash($existingVehicle ? 'Vehicle updated successfully.' : 'Vehicle created successfully.', 'success');
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('Vehicle save failed: ' . $throwable->getMessage(), 'danger');
}

redirect('dashboard.php?section=vehicles');


