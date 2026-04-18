<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/validation.php';

if (!is_post_request()) {
    json_response(false, 'Only POST requests are allowed.', [], 405);
}

$data = request_data();
$name = clean_value($data['name'] ?? '');
$method = clean_value($data['registration_method'] ?? 'email');
$email = clean_value($data['email'] ?? '');
$phone = clean_value($data['phone'] ?? '');
$password = (string) ($data['password'] ?? '');
$confirmPassword = (string) ($data['confirm_password'] ?? '');

if ($name === '' || $password === '' || $confirmPassword === '') {
    json_response(false, 'Please complete all required fields.', [], 422);
}

if (!in_array($method, ['email', 'phone'], true)) {
    json_response(false, 'Registration method must be email or phone.', [], 422);
}

if ($method === 'email' && !is_valid_email($email)) {
    json_response(false, 'Please enter a valid email address.', [], 422);
}

if ($method === 'phone' && !is_valid_phone($phone)) {
    json_response(false, 'Please enter a valid phone number.', [], 422);
}

if (strlen($password) < 6) {
    json_response(false, 'Password must be at least 6 characters long.', [], 422);
}

if ($password !== $confirmPassword) {
    json_response(false, 'Password confirmation does not match.', [], 422);
}

$users = read_json_file(USERS_FILE);

foreach ($users as $user) {
    if ($email !== '' && isset($user['email']) && strcasecmp((string) $user['email'], $email) === 0) {
        json_response(false, 'An account with this email already exists.', [], 409);
    }

    if ($phone !== '' && isset($user['phone']) && preg_replace('/\s+/', '', (string) $user['phone']) === preg_replace('/\s+/', '', $phone)) {
        json_response(false, 'An account with this phone number already exists.', [], 409);
    }
}

$users[] = [
    'id' => next_numeric_id($users),
    'name' => $name,
    'email' => $method === 'email' ? $email : '',
    'phone' => $method === 'phone' ? $phone : '',
    'registration_method' => $method,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'created_at' => date(DATE_ATOM),
];

write_json_file(USERS_FILE, $users);

json_response(true, 'Registration successful. You can log in now.', [
    'redirect_url' => app_url('signinpage.html?registered=1'),
]);
