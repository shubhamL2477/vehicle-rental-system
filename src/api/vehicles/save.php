<?php

require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, 'Only POST method is allowed.', array(), 405);
}

if (empty($_SESSION['user_id'])) {
    send_json(false, 'Login first.', array(), 401);
}

$pdo = get_db();
$user_id = (int) $_SESSION['user_id'];

$user_stmt = $pdo->prepare(
    'SELECT u.id, r.role_name
     FROM users u
     INNER JOIN roles r ON u.role_id = r.id
     WHERE u.id = ?
     LIMIT 1'
);
$user_stmt->execute(array($user_id));
$user = $user_stmt->fetch();

if (!$user) {
    send_json(false, 'User not found.', array(), 404);
}

if ($user['role_name'] !== 'super_admin' && $user['role_name'] !== 'company' && $user['role_name'] !== 'agent') {
    send_json(false, 'Only admin users can save vehicle details.', array(), 403);
}

$data = get_request_data();

$vehicle_id = isset($data['vehicle_id']) ? (int) $data['vehicle_id'] : 0;
$company_id = isset($data['company_id']) ? (int) $data['company_id'] : 0;
$name = isset($data['name']) ? trim($data['name']) : '';
$type = isset($data['type']) ? trim($data['type']) : '';
$price_per_day = isset($data['price_per_day']) ? (float) $data['price_per_day'] : 0;
$availability = isset($data['availability']) ? trim($data['availability']) : 'available';
$location = isset($data['location']) ? trim($data['location']) : '';
$latitude = isset($data['latitude']) ? $data['latitude'] : null;
$longitude = isset($data['longitude']) ? $data['longitude'] : null;

if ($name === '' || $type === '' || $price_per_day <= 0 || $location === '') {
    send_json(false, 'Name, type, price, and location are required.', array(), 422);
}

if ($vehicle_id > 0) {
    $update = $pdo->prepare(
        'UPDATE vehicles
         SET company_id = ?, name = ?, type = ?, price_per_day = ?, availability = ?, location = ?, latitude = ?, longitude = ?
         WHERE id = ?'
    );
    $update->execute(array(
        $company_id,
        $name,
        $type,
        $price_per_day,
        $availability,
        $location,
        $latitude,
        $longitude,
        $vehicle_id
    ));

    send_json(true, 'Vehicle updated successfully.', array(
        'vehicle_id' => $vehicle_id
    ));
}

$insert = $pdo->prepare(
    'INSERT INTO vehicles (company_id, name, type, price_per_day, availability, location, latitude, longitude, created_by_user_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$insert->execute(array(
    $company_id,
    $name,
    $type,
    $price_per_day,
    $availability,
    $location,
    $latitude,
    $longitude,
    $user_id
));

send_json(true, 'Vehicle created successfully.', array(
    'vehicle_id' => (int) $pdo->lastInsertId()
));
