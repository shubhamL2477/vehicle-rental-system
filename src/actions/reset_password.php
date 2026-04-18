<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_csrf();

$userId = (int) ($_SESSION['password_reset_user_id'] ?? 0);
$otpId = (int) ($_SESSION['password_reset_otp_id'] ?? 0);
$verified = (bool) ($_SESSION['password_reset_verified'] ?? false);
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['password_confirmation'] ?? '');

if (!$userId || !$verified) {
    set_flash('Reset session expired. Start again.', 'warning');
    redirect('forgot-password.php');
}

if (strlen($password) < 6) {
    set_flash('Password must be at least 6 characters.', 'danger');
    redirect('reset-password.php');
}

if ($password !== $confirmPassword) {
    set_flash('Password confirmation does not match.', 'danger');
    redirect('reset-password.php');
}

require_db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([
    password_hash($password, PASSWORD_DEFAULT),
    $userId,
]);

if ($otpId > 0) {
    mark_otp_used($otpId);
}

unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_verified'], $_SESSION['password_reset_otp_id']);

set_flash('Password updated successfully. Please log in.', 'success');
redirect('login.php');


