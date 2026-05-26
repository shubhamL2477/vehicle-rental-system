<?php

require_once __DIR__ . '/common.php';

api_require_post();

$viewer = current_user();

if (!$viewer) {
    api_response(false, 'Login first using the login API.', [], 401);
}

if (!has_role(['super_admin', 'company', 'agent'])) {
    api_response(false, 'Only admins can manage vehicles.', [], 403);
}

$data = api_data();
$vehicleId = isset($data['vehicle_id']) ? (int) $data['vehicle_id'] : 0;

if (has_role('super_admin')) {
    $companyId = isset($data['company_id']) ? (int) $data['company_id'] : 0;
} else {
    $companyId = (int) managed_company_id();
}

$name = isset($data['name']) ? trim($data['name']) : '';
$type = isset($data['type']) ? $data['type'] : '';
$pricePerDay = isset($data['price_per_day']) ? (float) $data['price_per_day'] : 0;
$location = isset($data['location']) ? trim($data['location']) : '';
$status = isset($data['status']) ? $data['status'] : 'available';
$latitude = isset($data['latitude']) ? $data['latitude'] : null;
$longitude = isset($data['longitude']) ? $data['longitude'] : null;

if ($companyId < 1 || $name === '' || !in_array($type, VEHICLE_TYPES, true) || $pricePerDay <= 0 || $location === '') {
    api_response(false, 'company_id, name, type, price_per_day, and location are required.', [], 422);
}

if (!in_array($status, VEHICLE_STATUSES, true)) {
    api_response(false, 'Invalid vehicle status.', [], 422);
}

if (!db_one('SELECT id FROM companies WHERE id = ?', [$companyId])) {
    api_response(false, 'Company not found.', [], 404);
}

$pdo = require_db();

if ($vehicleId > 0) {
    // Update old vehicle data.
    $pdo->prepare(
        'UPDATE vehicles SET name = ?, type = ?, price_per_day = ?, location = ?, status = ?, latitude = ?, longitude = ?
         WHERE id = ? AND company_id = ?'
    )->execute([$name, $type, $pricePerDay, $location, $status, $latitude, $longitude, $vehicleId, $companyId]);

    api_response(true, 'Vehicle updated successfully.', ['vehicle_id' => $vehicleId]);
}

// Create new vehicle if id is not sent.
$pdo->prepare(
    'INSERT INTO vehicles (company_id, name, type, price_per_day, driver_price_per_day, seating_capacity, location, status, latitude, longitude, created_by_user_id)
     VALUES (?, ?, ?, ?, 0, 4, ?, ?, ?, ?, ?)'
)->execute([$companyId, $name, $type, $pricePerDay, $location, $status, $latitude, $longitude, $viewer['id']]);

api_response(true, 'Vehicle created successfully.', [
    'vehicle_id' => (int) $pdo->lastInsertId(),
]);
