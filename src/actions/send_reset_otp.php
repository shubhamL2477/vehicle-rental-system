<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_csrf();

$credential = trim((string) ($_POST['credential'] ?? ''));
remember_input(['credential' => $credential]);

if ($credential === '') {
    set_flash('Phone or email is required.', 'danger');
    redirect('forgot-password.php');
}

$user = db_one('SELECT * FROM users WHERE email = ? OR phone = ?', [$credential, $credential]);

if (!$user) {
    set_flash('Account not found.', 'danger');
    redirect('forgot-password.php');
}

$latestOtp = latest_sent_otp((int) $user['id'], 'password_reset');

if ($latestOtp) {
    $secondsLeft = otp_resend_seconds_left($latestOtp);

    if ($secondsLeft > 0) {
        set_flash(
            'Please wait ' . $secondsLeft . ' seconds before requesting a new OTP.',
            'warning'
        );
        redirect('verify-reset-otp.php?credential=' . urlencode($credential));
    }
}

$activeOtp = latest_active_otp((int) $user['id'], 'password_reset');

if ($activeOtp) {
    $secondsLeft = otp_seconds_left($activeOtp);
    set_flash(
        'A password reset OTP is already active. Please use that OTP or wait ' . $secondsLeft . ' seconds.',
        'warning'
    );
    redirect('verify-reset-otp.php?credential=' . urlencode($credential));
}

$otp = create_otp((int) $user['id'], 'password_reset');

try {
    send_password_reset_otp_email($user['email'], $user['name'], $otp['otp_code']);
    set_flash('A password reset OTP has been sent to your email. It expires in 60 seconds.', 'success');
} catch (Throwable $error) {
    set_flash('Could not send password reset OTP email. ' . $error->getMessage(), 'danger');
}

redirect('verify-reset-otp.php?credential=' . urlencode($credential));


