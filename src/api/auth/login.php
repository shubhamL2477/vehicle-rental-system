<?php

require_once __DIR__ . '/common.php';

if (!is_post()) {
    redirect('login.php');
}

require_csrf();

$credential = isset($_POST['credential']) ? trim($_POST['credential']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

remember_input(['credential' => $credential]);

if ($credential === '' || $password === '') {
    set_flash('Email/phone and password are required.', 'danger');
    redirect('login.php');
}

$user = db_one(
    'SELECT * FROM users WHERE email = :credential OR phone = :credential LIMIT 1',
    ['credential' => $credential]
);

if (!$user || !password_verify($password, $user['password'])) {
    set_flash('Invalid credentials. Please try again.', 'danger');
    redirect('login.php');
}

if (!auth_is_verified($user)) {
    set_flash('Please verify your email before logging in.', 'warning');
    redirect('verify-email.php?email=' . urlencode($user['email']));
}

if ($user['status'] === 'pending') {
    set_flash('Your account is still pending. Please complete OTP verification first.', 'warning');
    redirect('login.php');
}

if ($user['status'] !== 'active') {
    $message = 'Your account is not active yet.';

    if ($user['role'] === 'company') {
        $message = 'Your company account is waiting for admin approval.';
    }

    set_flash($message, 'warning');
    redirect('login.php');
}

if ($user['role'] === 'company') {
    $company = db_one('SELECT status FROM companies WHERE owner_user_id = ?', [(int) $user['id']]);

    if (!$company || $company['status'] !== 'approved') {
        set_flash('Your company profile has not been approved yet.', 'warning');
        redirect('login.php');
    }
}

if ($user['role'] === 'agent') {
    $agent = db_one(
        'SELECT a.status AS agent_status, c.status AS company_status
         FROM agents a
         INNER JOIN companies c ON c.id = a.company_id
         WHERE a.user_id = ?',
        [(int) $user['id']]
    );

    if (!$agent || $agent['agent_status'] !== 'active' || $agent['company_status'] !== 'approved') {
        set_flash('Your agent account is not active right now.', 'warning');
        redirect('login.php');
    }
}

login_user($user);
set_flash('Welcome back, ' . $user['name'] . '!', 'success');
redirect('dashboard.php');
