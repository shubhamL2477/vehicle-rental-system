<?php

require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, 'Only POST method is allowed.', array(), 405);
}

$data = get_request_data();

$credential = isset($data['credential']) ? trim($data['credential']) : '';
$password = isset($data['password']) ? $data['password'] : '';

if ($credential === '' || $password === '') {
    send_json(false, 'Credential and password are required.', array(), 422);
}

$user = find_user_by_credential($credential);

if (!$user) {
    send_json(false, 'User not found.', array(), 404);
}

if (!password_verify($password, $user['password'])) {
    send_json(false, 'Password is incorrect.', array(), 401);
}

if ($user['status'] !== 'active') {
    send_json(false, 'Account is not active. Verify OTP first.', array(
        'status' => $user['status']
    ), 403);
}

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['role_id'] = (int) $user['role_id'];

send_json(true, 'Login successful.', array(
    'session_id' => session_id(),
    'user' => array(
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'status' => $user['status']
    )
));
