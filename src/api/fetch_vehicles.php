<?php

require_once __DIR__ . '/common.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$companyId = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;

if ($limit < 1) {
    $limit = 50;
}

if ($limit > 100) {
    $limit = 100;
}

$companies = db_all("SELECT id, name FROM companies WHERE status = 'approved' ORDER BY name");
$companyMap = [];

foreach ($companies as $company) {
    $companyMap[$company['id']] = $company['name'];
}

$rows = db_all('SELECT * FROM vehicles ORDER BY name ASC');
$vehicles = [];

foreach ($rows as $vehicle) {
    if (!isset($companyMap[$vehicle['company_id']])) {
        continue;
    }

    $matches = true;
    $companyName = $companyMap[$vehicle['company_id']];
    $searchText = strtolower($search);

    if ($searchText !== '') {
        $fullText = strtolower($vehicle['name'] . ' ' . $companyName . ' ' . $vehicle['location'] . ' ' . $vehicle['type']);

        if (strpos($fullText, $searchText) === false) {
            $matches = false;
        }
    }

    if ($matches && $type !== '' && in_array($type, VEHICLE_TYPES, true) && $vehicle['type'] !== $type) {
        $matches = false;
    }

    if ($matches && $companyId > 0 && (int) $vehicle['company_id'] !== $companyId) {
        $matches = false;
    }

    if ($matches && $location !== '' && stripos($vehicle['location'], $location) === false) {
        $matches = false;
    }

    if ($matches && $status !== '' && in_array($status, VEHICLE_STATUSES, true) && $vehicle['status'] !== $status) {
        $matches = false;
    }

    if (!$matches) {
        continue;
    }

    $vehicle['id'] = (int) $vehicle['id'];
    $vehicle['company_id'] = (int) $vehicle['company_id'];
    $vehicle['price_per_day'] = (float) $vehicle['price_per_day'];
    $vehicle['driver_price_per_day'] = (float) $vehicle['driver_price_per_day'];
    $vehicle['latitude'] = $vehicle['latitude'] !== null ? (float) $vehicle['latitude'] : null;
    $vehicle['longitude'] = $vehicle['longitude'] !== null ? (float) $vehicle['longitude'] : null;
    $vehicle['company_name'] = $companyName;
    $vehicle['image_name'] = db_value(
        'SELECT file_name FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1',
        [$vehicle['id']]
    );
    $vehicle['image_url'] = $vehicle['image_name'] ? upload_url(VEHICLE_UPLOAD_DIR, $vehicle['image_name']) : null;
    $vehicles[] = $vehicle;

    if (count($vehicles) >= $limit) {
        break;
    }
}

usort($vehicles, function ($a, $b) {
    if ($a['status'] === $b['status']) {
        return strcmp($a['name'], $b['name']);
    }

    if ($a['status'] === 'available') {
        return -1;
    }

    if ($b['status'] === 'available') {
        return 1;
    }

    return strcmp($a['name'], $b['name']);
});

api_response(true, 'Vehicles fetched successfully.', [
    'count' => count($vehicles),
    'filters' => [
        'search' => $search,
        'type' => $type,
        'company_id' => $companyId,
        'location' => $location,
        'status' => $status,
        'limit' => $limit,
    ],
    'vehicles' => $vehicles,
]);
