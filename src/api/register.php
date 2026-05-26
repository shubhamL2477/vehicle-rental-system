<?php

require_once __DIR__ . '/common.php';

api_require_post();

$data = api_data();
$name = trim((string) ($data['name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$password = (string) ($data['password'] ?? '');
$role = (string) ($data['role'] ?? 'user');
$address = trim((string) ($data['address'] ?? ''));
$companyName = trim((string) ($data['company_name'] ?? ''));

if ($name === '' || $email === '' || $phone === '' || $password === '') {
    api_response(false, 'Name, email, phone, and password are required.', [], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_response(false, 'Invalid email format.', [], 422);
}

if (strlen($password) < 6) {
    api_response(false, 'Password must be at least 6 characters.', [], 422);
}

if (!in_array($role, ['user', 'company'], true)) {
    api_response(false, 'Role must be user or company.', [], 422);
}

if ($role === 'company' && $companyName === '') {
    api_response(false, 'Company name is required for company registration.', [], 422);
}

if (db_one('SELECT id FROM users WHERE email = ? OR phone = ?', [$email, $phone])) {
    api_response(false, 'Email or phone already exists.', [], 409);
}

$pdo = require_db();

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        'INSERT INTO users (role_id, name, email, phone, password, role, status, address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        role_id_by_name($role),
        $name,
        $email,
        $phone,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
        'pending',
        $address,
    ]);

    $userId = (int) $pdo->lastInsertId();

    if ($role === 'company') {
        $pdo->prepare(
            'INSERT INTO companies (owner_user_id, name, description, address, contact_email, contact_phone, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $companyName, '', $address, $email, $phone, 'pending']);
    }

    $otp = create_demo_otp($userId);
    $pdo->commit();

    api_response(true, 'Registration complete. Verify OTP to continue.', [
        'user_id' => $userId,
        'role' => $role,
        'demo_otp' => $otp['otp_code'],
        'otp_expires_at' => $otp['expires_at'],
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    api_response(false, 'Registration failed: ' . $error->getMessage(), [], 500);
}
