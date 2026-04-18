<?php

require_once __DIR__ . '/../config/constants.php';

function ensure_data_store()
{
    if (!is_dir(DATA_DIRECTORY)) {
        mkdir(DATA_DIRECTORY, 0777, true);
    }

    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, json_encode([], JSON_PRETTY_PRINT));
    }

    if (!file_exists(VEHICLES_FILE)) {
        file_put_contents(VEHICLES_FILE, json_encode([], JSON_PRETTY_PRINT));
    }
}

function read_json_file($path)
{
    ensure_data_store();

    $content = file_get_contents($path);

    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function write_json_file($path, $data)
{
    ensure_data_store();
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function next_numeric_id($items)
{
    $max = 0;

    foreach ($items as $item) {
        $candidate = isset($item['id']) ? (int) $item['id'] : 0;

        if ($candidate > $max) {
            $max = $candidate;
        }
    }

    return $max + 1;
}

function request_method()
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post_request()
{
    return request_method() === 'POST';
}

function request_data()
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    $raw = file_get_contents('php://input');

    if (strpos($contentType, 'application/json') !== false && $raw) {
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return $_POST;
}

function app_base_url()
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = preg_replace('#/src/.*$#', '', $scriptName);
    return rtrim((string) $base, '/');
}

function app_url($path = '')
{
    $base = app_base_url();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function redirect_to($path)
{
    header('Location: ' . app_url($path));
    exit;
}

function json_response($success, $message, $data = [], $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'redirect_url' => $data['redirect_url'] ?? null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
