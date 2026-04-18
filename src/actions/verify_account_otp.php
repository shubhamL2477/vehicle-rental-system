<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_csrf();

$credential = trim((string) ($_POST['credential'] ?? ''));
$otpCode = trim((string) ($_POST['otp_code'] ?? ''));
remember_input(['credential' => $credential]);

if ($credential === '' || $otpCode === '') {
    set_flash('Phone/email and OTP are required.', 'danger');
    redirect('verify-account.php');
}

$user = db_one('SELECT * FROM users WHERE email = ? OR phone = ?', [$credential, $credential]);

if (!$user) {
    set_flash('Account not found.', 'danger');
    redirect('verify-account.php');
}

$otp = find_valid_otp((int) $user['id'], $otpCode, 'account_verification');

if (!$otp) {
    set_flash('Invalid or expired OTP. If the code expired after 60 seconds, click Send OTP again.', 'danger');
    redirect('verify-account.php?credential=' . urlencode($credential));
}

$pdo = require_db();

try {
    $pdo->beginTransaction();
    mark_otp_used((int) $otp['id']);
    $pdo->prepare(
        'UPDATE users
         SET status = ?, is_verified = 1, verified_at = NOW()
         WHERE id = ?'
    )->execute(['active', $user['id']]);
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('OTP verification failed. Please try again.', 'danger');
    redirect('verify-account.php?credential=' . urlencode($credential));
}

if ($user['role'] === 'company') {
    set_flash('OTP verified. Company account now waits for super admin approval.', 'success');
} else {
    set_flash('OTP verified. You can now log in.', 'success');
}

redirect('login.php');


