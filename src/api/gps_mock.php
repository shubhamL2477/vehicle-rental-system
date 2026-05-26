<?php

require_once __DIR__ . '/common.php';

// This API does not use real GPS.
// It only changes vehicle latitude and longitude with fixed sample values.
$vehicleId = isset($_GET['vehicle_id']) ? (int) $_GET['vehicle_id'] : 0;

if ($vehicleId < 1) {
    api_response(false, 'vehicle_id is required in query string.', [], 422);
}

$vehicle = db_one('SELECT id, name, latitude, longitude FROM vehicles WHERE id = ?', [$vehicleId]);

if (!$vehicle) {
    api_response(false, 'Vehicle not found.', [], 404);
}

$route = [
    ['lat' => 27.7172, 'lng' => 85.3240],
    ['lat' => 27.7081, 'lng' => 85.3296],
    ['lat' => 27.6966, 'lng' => 85.3591],
    ['lat' => 27.7008, 'lng' => 85.3333],
];

$step = (int) date('i') % count($route);
$newLat = $route[$step]['lat'];
$newLng = $route[$step]['lng'];

require_db()->prepare('UPDATE vehicles SET latitude = ?, longitude = ? WHERE id = ?')->execute([
    $newLat,
    $newLng,
    $vehicleId,
]);

api_response(true, 'Mock GPS updated.', [
    'vehicle_id' => $vehicleId,
    'vehicle_name' => $vehicle['name'],
    'latitude' => $newLat,
    'longitude' => $newLng,
    'note' => 'This is dummy GPS data for project demo.',
]);
