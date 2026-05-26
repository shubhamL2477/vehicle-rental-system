<?php
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $_GET['action'] = 'vehicles';
} elseif ($method === 'POST' || $method === 'PUT') {
    $_GET['action'] = 'vehicle_save';
} elseif ($method === 'DELETE') {
    $_GET['action'] = 'vehicle_delete';
} else {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unsupported method.', 'data' => []]);
    exit;
}

require __DIR__ . '/../api.php';
