<?php

require_once __DIR__ . '/common.php';

$query = '';

if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
} elseif (isset($_GET['search'])) {
    $query = trim($_GET['search']);
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$companyId = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

if ($limit < 1) {
    $limit = 20;
}

if ($limit > 20) {
    $limit = 20;
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
    $searchText = strtolower($query);

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

    if (!$matches) {
        continue;
    }

    $vehicle['id'] = (int) $vehicle['id'];
    $vehicle['company_id'] = (int) $vehicle['company_id'];
    $vehicle['price_per_day'] = (float) $vehicle['price_per_day'];
    $vehicle['driver_price_per_day'] = (float) $vehicle['driver_price_per_day'];
    $vehicle['company_name'] = $companyName;
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

api_response(true, 'Vehicle search completed.', [
    'query' => $query,
    'count' => count($vehicles),
    'vehicles' => $vehicles,
]);
