<?php

require_once __DIR__ . '/../../includes/functions.php';

$vehicle_id = isset($_GET['vehicle_id']) ? (int) $_GET['vehicle_id'] : 0;

if ($vehicle_id < 1) {
    send_json(false, 'vehicle_id is required.', array(), 422);
}

$pdo = get_db();
$find = $pdo->prepare('SELECT * FROM vehicles WHERE id = ? LIMIT 1');
$find->execute(array($vehicle_id));
$vehicle = $find->fetch();

if (!$vehicle) {
    send_json(false, 'Vehicle not found.', array(), 404);
}

// Dummy GPS points for project demo
$points = array(
    array('lat' => 27.7172, 'lng' => 85.3240),
    array('lat' => 27.7081, 'lng' => 85.3296),
    array('lat' => 27.6966, 'lng' => 85.3591),
    array('lat' => 27.7008, 'lng' => 85.3333)
);

$step = (int) date('i') % count($points);
$new_lat = $points[$step]['lat'];
$new_lng = $points[$step]['lng'];

$update = $pdo->prepare('UPDATE vehicles SET latitude = ?, longitude = ? WHERE id = ?');
$update->execute(array($new_lat, $new_lng, $vehicle_id));

send_json(true, 'Mock GPS updated.', array(
    'vehicle_id' => $vehicle_id,
    'vehicle_name' => $vehicle['name'],
    'latitude' => $new_lat,
    'longitude' => $new_lng
));
