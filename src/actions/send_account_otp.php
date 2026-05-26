<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_csrf();

$credential = trim((string) ($_POST['credential'] ?? ''));
remember_input(['credential' => $credential]);

if ($credential === '') {
    set_flash('Phone or email is required.', 'danger');
    redirect('verify-account.php');
}

$user = db_one('SELECT * FROM users WHERE email = ? OR phone = ?', [$credential, $credential]);

if (!$user) {
    set_flash('Account not found.', 'danger');
    redirect('verify-account.php');
}

$activeOtp = latest_active_otp((int) $user['id'], 'account_verification');

if ($activeOtp) {
    $secondsLeft = otp_seconds_left($activeOtp);
    set_flash('Please wait ' . $secondsLeft . ' seconds before sending a new OTP.', 'warning');
    redirect('verify-account.php?credential=' . urlencode($credential));
}

$otp = create_otp((int) $user['id'], 'account_verification');
set_flash('Demo OTP: ' . $otp['otp_code'] . '. This OTP expires in 60 seconds. Real project needs SMS API to send this to phone.', 'success');
redirect('verify-account.php?credential=' . urlencode($credential));


