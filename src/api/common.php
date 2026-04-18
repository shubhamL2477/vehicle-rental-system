<?php

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

function api_response($success, $message, $data = [], $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_PRETTY_PRINT);

    exit;
}

function api_require_post()
{
    if (!is_post()) {
        api_response(false, 'Only POST request is allowed.', [], 405);
    }
}

function api_data()
{
    $rawBody = file_get_contents('php://input');

    if ($rawBody === false) {
        $rawBody = '';
    }

    $json = json_decode($rawBody, true);

    if (is_array($json) && !empty($json)) {
        return $json;
    }

    return $_POST;
}

function create_demo_otp($userId, $purpose = 'account_verification')
{
    return create_otp($userId, $purpose);
}
