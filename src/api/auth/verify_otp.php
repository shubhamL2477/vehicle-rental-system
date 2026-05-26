<?php

require_once __DIR__ . '/common.php';

if (!is_post()) {
    redirect('verify-email.php');
}

require_csrf();

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$otpCode = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';
remember_input(['email' => $email]);

if ($email === '' || $otpCode === '') {
    set_flash('Email and OTP are required.', 'danger');
    redirect('verify-email.php');
}

$user = auth_find_user_by_email($email);

if (!$user) {
    set_flash('Account not found for this email.', 'danger');
    redirect('verify-email.php');
}

if (auth_is_verified($user)) {
    set_flash('Your email is already verified. Please log in.', 'success');
    redirect('login.php');
}

$otp = find_valid_otp((int) $user['id'], $otpCode, 'account_verification');

if (!$otp) {
    set_flash('Invalid or expired OTP. Please try again or resend OTP.', 'danger');
    redirect('verify-email.php?email=' . urlencode($email));
}

$pdo = require_db();

try {
    $pdo->beginTransaction();

    // Mark the OTP as used and make the account active.
    mark_otp_used((int) $otp['id']);
    auth_mark_user_verified((int) $user['id']);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('OTP verification failed. Please try again.', 'danger');
    redirect('verify-email.php?email=' . urlencode($email));
}

if ($user['role'] === 'company') {
    set_flash('Email verified. Your company account now waits for admin approval.', 'success');
} else {
    set_flash('Email verified. You can now log in.', 'success');
}

redirect('login.php');
