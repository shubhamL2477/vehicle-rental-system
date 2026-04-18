<?php

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/validation.php';

$vehicles = read_json_file(VEHICLES_FILE);
$search = clean_value($_GET['search'] ?? '');
$type = clean_value($_GET['type'] ?? '');
$location = clean_value($_GET['location'] ?? '');
$availability = clean_value($_GET['availability'] ?? '');
$maxPrice = isset($_GET['max_price']) ? (float) $_GET['max_price'] : 0;

$filtered = array_values(array_filter($vehicles, function ($vehicle) use ($search, $type, $location, $availability, $maxPrice) {
    if ($search !== '') {
        $haystack = strtolower(
            (string) ($vehicle['name'] ?? '') . ' ' .
            (string) ($vehicle['type'] ?? '') . ' ' .
            (string) ($vehicle['location'] ?? '')
        );

        if (strpos($haystack, strtolower($search)) === false) {
            return false;
        }
    }

    if ($type !== '' && strcasecmp((string) ($vehicle['type'] ?? ''), $type) !== 0) {
        return false;
    }

    if ($location !== '' && stripos((string) ($vehicle['location'] ?? ''), $location) === false) {
        return false;
    }

    if ($availability !== '' && strcasecmp((string) ($vehicle['availability'] ?? ''), $availability) !== 0) {
        return false;
    }

    if ($maxPrice > 0 && (float) ($vehicle['price_per_day'] ?? 0) > $maxPrice) {
        return false;
    }

    return true;
}));

json_response(true, 'Vehicles fetched successfully.', [
    'count' => count($filtered),
    'filters' => [
        'search' => $search,
        'type' => $type,
        'location' => $location,
        'availability' => $availability,
        'max_price' => $maxPrice,
    ],
    'vehicles' => $filtered,
]);
