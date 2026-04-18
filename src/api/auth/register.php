<?php

require_once __DIR__ . '/common.php';

if (!is_post()) {
    redirect('register.php');
}

require_csrf();

$role = isset($_POST['role']) ? $_POST['role'] : 'user';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['password_confirmation']) ? $_POST['password_confirmation'] : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$companyName = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
$companyDescription = isset($_POST['company_description']) ? trim($_POST['company_description']) : '';

remember_input([
    'role' => $role,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'address' => $address,
    'company_name' => $companyName,
    'company_description' => $companyDescription,
]);

if ($role !== 'user' && $role !== 'company') {
    set_flash('You can register only as a renter or a company.', 'danger');
    redirect('register.php');
}

if ($name === '' || $email === '' || $phone === '' || $password === '') {
    set_flash('Please complete all required fields.', 'danger');
    redirect('register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('Please provide a valid email address.', 'danger');
    redirect('register.php');
}

if (strlen($password) < 6) {
    set_flash('Password must be at least 6 characters long.', 'danger');
    redirect('register.php');
}

if ($password !== $confirmPassword) {
    set_flash('Password confirmation does not match.', 'danger');
    redirect('register.php');
}

if ($role === 'company' && $companyName === '') {
    set_flash('Company name is required for company registration.', 'danger');
    redirect('register.php');
}

if (db_one('SELECT id FROM users WHERE email = ? OR phone = ?', [$email, $phone])) {
    set_flash('An account with this email or phone already exists.', 'danger');
    redirect('register.php');
}

$pdo = require_db();

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        'INSERT INTO users (role_id, name, email, phone, password, role, status, is_verified, address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        role_id_by_name($role),
        $name,
        $email,
        $phone,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
        'pending',
        0,
        $address,
    ]);

    $userId = (int) $pdo->lastInsertId();

    if ($role === 'company') {
        $pdo->prepare(
            'INSERT INTO companies (owner_user_id, name, description, address, contact_email, contact_phone, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $userId,
            $companyName,
            $companyDescription,
            $address,
            $email,
            $phone,
            'pending',
        ]);
    }

    $otp = create_otp($userId, 'account_verification');
    $pdo->commit();

    try {
        send_verification_otp_email($email, $name, $otp['otp_code']);
        set_flash('Registration successful. We sent a verification code to your email.', 'success');
    } catch (Throwable $e) {
        set_flash('Account created, but email could not be sent. ' . $e->getMessage(), 'warning');
    }

    redirect('verify-email.php?email=' . urlencode($email));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('Registration failed: ' . $e->getMessage(), 'danger');
    redirect('register.php');
}
