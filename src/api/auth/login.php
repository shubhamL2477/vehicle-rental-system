<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/validation.php';

if (!is_post_request()) {
    json_response(false, 'Only POST requests are allowed.', [], 405);
}

$data = request_data();
$credential = clean_value($data['credential'] ?? '');
$password = (string) ($data['password'] ?? '');

if ($credential === '' || $password === '') {
    json_response(false, 'Email/phone and password are required.', [], 422);
}

$users = read_json_file(USERS_FILE);
$matchedUser = null;

foreach ($users as $user) {
    $email = (string) ($user['email'] ?? '');
    $phone = preg_replace('/\s+/', '', (string) ($user['phone'] ?? ''));
    $normalizedCredential = preg_replace('/\s+/', '', $credential);

    if (
        ($email !== '' && strcasecmp($email, $credential) === 0) ||
        ($phone !== '' && $phone === $normalizedCredential)
    ) {
        $matchedUser = $user;
        break;
    }
}

if (!$matchedUser || !password_verify($password, (string) $matchedUser['password'])) {
    json_response(false, 'Invalid login credentials.', [], 401);
}

login_user($matchedUser);

json_response(true, 'Login successful.', [
    'user' => current_user(),
]);
