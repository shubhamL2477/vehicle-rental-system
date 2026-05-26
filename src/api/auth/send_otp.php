<?php

require_once __DIR__ . '/common.php';

if (!is_post()) {
    redirect('verify-email.php');
}

require_csrf();

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
remember_input(['email' => $email]);

if ($email === '') {
    set_flash('Email is required.', 'danger');
    redirect('verify-email.php');
}

$user = auth_find_user_by_email($email);

if (!$user) {
    set_flash('Account not found for this email.', 'danger');
    redirect('verify-email.php');
}

if (auth_is_verified($user)) {
    set_flash('This email is already verified. You can log in now.', 'success');
    redirect('login.php');
}

$latestOtp = latest_sent_otp((int) $user['id'], 'account_verification');

if ($latestOtp) {
    $secondsLeft = otp_resend_seconds_left($latestOtp);

    if ($secondsLeft > 0) {
        set_flash('Please wait ' . $secondsLeft . ' seconds before sending a new OTP.', 'warning');
        redirect('verify-email.php?email=' . urlencode($email));
    }
}

$activeOtp = latest_active_otp((int) $user['id'], 'account_verification');

if ($activeOtp) {
    $secondsLeft = otp_seconds_left($activeOtp);
    set_flash('A verification OTP is already active. Please use that OTP or wait ' . $secondsLeft . ' seconds.', 'warning');
    redirect('verify-email.php?email=' . urlencode($email));
}

$otp = create_otp((int) $user['id'], 'account_verification');

try {
    send_verification_otp_email($user['email'], $user['name'], $otp['otp_code']);
    set_flash('A new OTP has been sent to your email.', 'success');
} catch (Throwable $e) {
    set_flash('Could not send OTP email. ' . $e->getMessage(), 'danger');
}

redirect('verify-email.php?email=' . urlencode($email));
