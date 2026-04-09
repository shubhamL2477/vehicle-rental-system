<?php

require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, 'Only POST method is allowed.', array(), 405);
}

$data = get_request_data();

$name = isset($data['name']) ? trim($data['name']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$role = isset($data['role']) ? trim($data['role']) : 'user';

if ($name === '' || $email === '' || $phone === '' || $password === '') {
    send_json(false, 'Name, email, phone, and password are required.', array(), 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json(false, 'Enter a valid email address.', array(), 422);
}

if (strlen($password) < 6) {
    send_json(false, 'Password must be at least 6 characters.', array(), 422);
}

$role_id = find_role_id($role);

if ($role_id === 0) {
    send_json(false, 'Invalid role.', array(), 422);
}

$pdo = get_db();

$check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1');
$check->execute(array($email, $phone));

if ($check->fetch()) {
    send_json(false, 'Email or phone already exists.', array(), 409);
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO users (role_id, name, email, phone, password, status)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute(array($role_id, $name, $email, $phone, $hashed_password, 'pending'));

$user_id = (int) $pdo->lastInsertId();
$otp = create_otp($user_id);

send_json(true, 'Registration successful. Verify OTP to activate account.', array(
    'user_id' => $user_id,
    'otp_code' => $otp['otp_code'],
    'otp_expires_at' => $otp['otp_expires_at']
));
