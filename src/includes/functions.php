<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';

function send_json($success, $message, $data = array(), $status_code = 200)
{
    http_response_code($status_code);
    header('Content-Type: application/json');

    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $data
    ));
    exit;
}

function get_request_data()
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');

    if ($raw === false || $raw === '') {
        return array();
    }

    $json = json_decode($raw, true);

    if (is_array($json)) {
        return $json;
    }

    return array();
}

function find_role_id($role_name)
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE role_name = ? LIMIT 1');
    $stmt->execute(array($role_name));
    $row = $stmt->fetch();

    if ($row) {
        return (int) $row['id'];
    }

    return 0;
}

function find_user_by_credential($credential)
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1');
    $stmt->execute(array($credential, $credential));
    return $stmt->fetch();
}

function create_otp($user_id)
{
    $pdo = get_db();
    $otp_code = strval(rand(100000, 999999));
    $otp_expires_at = date('Y-m-d H:i:s', time() + 60);

    $clear = $pdo->prepare('UPDATE otp_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0');
    $clear->execute(array($user_id));

    $stmt = $pdo->prepare(
        'INSERT INTO otp_codes (user_id, otp_code, otp_expires_at, is_used, created_at)
         VALUES (?, ?, ?, 0, NOW())'
    );
    $stmt->execute(array($user_id, $otp_code, $otp_expires_at));

    return array(
        'otp_code' => $otp_code,
        'otp_expires_at' => $otp_expires_at
    );
}
