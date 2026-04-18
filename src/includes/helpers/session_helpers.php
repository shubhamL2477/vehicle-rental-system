<?php

function is_post()
{
    $method = 'GET';

    if (isset($_SERVER['REQUEST_METHOD'])) {
        $method = $_SERVER['REQUEST_METHOD'];
    }

    return strtoupper($method) === 'POST';
}

function set_flash($message, $type = 'info')
{
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function pull_flash()
{
    $message = null;

    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
    }

    return $message;
}

function remember_input($data)
{
    $_SESSION['old_input'] = $data;
}

function pull_old_input()
{
    $data = [];

    if (isset($_SESSION['old_input'])) {
        $data = $_SESSION['old_input'];
        unset($_SESSION['old_input']);
    }

    return $data;
}

function old($key, $default = '')
{
    if (isset($GLOBALS['old_input'][$key])) {
        return (string) $GLOBALS['old_input'][$key];
    }

    return $default;
}

function csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf()
{
    $token = '';
    $sessionToken = '';

    if (isset($_POST['_token'])) {
        $token = $_POST['_token'];
    }

    if (isset($_SESSION['csrf_token'])) {
        $sessionToken = $_SESSION['csrf_token'];
    }

    return is_string($token) && is_string($sessionToken) && hash_equals($sessionToken, $token);
}

function require_csrf()
{
    if (!is_post() || !verify_csrf()) {
        http_response_code(419);
        exit('Invalid or missing CSRF token.');
    }
}
