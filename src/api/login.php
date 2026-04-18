<?php

require_once __DIR__ . '/common.php';

api_require_post();

$data = api_data();
$credential = trim((string) ($data['credential'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($credential === '' || $password === '') {
    api_response(false, 'Credential and password are required.', [], 422);
}

$user = db_one('SELECT * FROM users WHERE email = ? OR phone = ?', [$credential, $credential]);

if (!$user || !password_verify($password, $user['password'])) {
    api_response(false, 'Invalid login details.', [], 401);
}

if ($user['status'] !== 'active') {
    api_response(false, 'Account is not active. Verify OTP or wait for approval.', [
        'status' => $user['status'],
        'role' => $user['role'],
    ], 403);
}

login_user($user);

api_response(true, 'Login successful.', [
    'session_id' => session_id(),
    'user' => [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'status' => $user['status'],
    ],
]);
