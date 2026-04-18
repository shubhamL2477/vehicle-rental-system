<?php

function app_path($path = '')
{
    if ($path === '') {
        return APP_ROOT;
    }

    return APP_ROOT . '/' . ltrim($path, '/');
}

function base_url()
{
    static $baseUrl = null;

    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $scriptName = '';

    if (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptName = $_SERVER['SCRIPT_NAME'];
    }

    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    $scriptDir = preg_replace('#/(actions|pages|api)(/.*)?$#', '', $scriptDir);
    $baseUrl = rtrim((string) $scriptDir, '/');

    return $baseUrl;
}

function url($path = '')
{
    $base = base_url();
    $path = ltrim($path, '/');

    if ($path === '') {
        if ($base === '') {
            return '/';
        }

        return $base . '/';
    }

    if ($base === '') {
        return '/' . $path;
    }

    return $base . '/' . $path;
}

function asset_url($path)
{
    return url('public/assets/' . ltrim($path, '/'));
}

function upload_url($folder, $fileName)
{
    return url(trim($folder, '/') . '/' . rawurlencode($fileName));
}

function redirect($path)
{
    if (preg_match('#^https?://#', $path)) {
        header('Location: ' . $path);
        exit;
    }

    header('Location: ' . url($path));
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function query_url($changes = [])
{
    $query = $_GET;

    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    $requestPath = 'dashboard.php';

    if (isset($_SERVER['REQUEST_URI'])) {
        $requestPath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    }

    $queryString = http_build_query($query);

    if ($queryString === '') {
        return url($requestPath);
    }

    return url($requestPath . '?' . $queryString);
}
