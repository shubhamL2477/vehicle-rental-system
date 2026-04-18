<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_csrf();

$credential = trim((string) ($_POST['credential'] ?? ''));
$otpCode = trim((string) ($_POST['otp_code'] ?? ''));
remember_input(['credential' => $credential]);

if ($credential === '' || $otpCode === '') {
    set_flash('Phone/email and OTP are required.', 'danger');
    redirect('verify-reset-otp.php');
}

$user = db_one('SELECT * FROM users WHERE email = ? OR phone = ?', [$credential, $credential]);

if (!$user) {
    set_flash('Account not found.', 'danger');
    redirect('forgot-password.php');
}

$otp = find_valid_otp((int) $user['id'], $otpCode, 'password_reset');

if (!$otp) {
    set_flash('Invalid or expired reset OTP. If the code expired after 60 seconds, click Send OTP again.', 'danger');
    redirect('verify-reset-otp.php?credential=' . urlencode($credential));
}

$_SESSION['password_reset_user_id'] = (int) $user['id'];
$_SESSION['password_reset_verified'] = true;
$_SESSION['password_reset_otp_id'] = (int) $otp['id'];

set_flash('OTP verified. You can now set a new password.', 'success');
redirect('reset-password.php');


