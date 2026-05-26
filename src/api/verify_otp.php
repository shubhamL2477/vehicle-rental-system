<?php

require_once __DIR__ . '/common.php';

api_require_post();

$data = api_data();
$credential = trim((string) ($data['credential'] ?? ''));
$otpCode = trim((string) ($data['otp_code'] ?? ''));
$purpose = (string) ($data['purpose'] ?? 'account_verification');

if ($credential === '' || $otpCode === '') {
    api_response(false, 'Credential and OTP code are required.', [], 422);
}

$user = db_one('SELECT * FROM users WHERE email = ? OR phone = ?', [$credential, $credential]);

if (!$user) {
    api_response(false, 'User not found.', [], 404);
}

$otp = find_valid_otp((int) $user['id'], $otpCode, $purpose);

if (!$otp) {
    api_response(false, 'Invalid or expired OTP code.', [], 422);
}

$pdo = require_db();
$pdo->beginTransaction();

try {
    mark_otp_used((int) $otp['id']);

    $newStatus = 'active';
    $pdo->prepare(
        'UPDATE users
         SET status = ?, is_verified = 1, verified_at = NOW()
         WHERE id = ?'
    )->execute([$newStatus, $user['id']]);
    $pdo->commit();

    $message = $user['role'] === 'company'
        ? 'OTP verified. Company account is waiting for super admin approval.'
        : 'OTP verified. User account is now active.';

    api_response(true, $message, [
        'user_id' => (int) $user['id'],
        'status' => $newStatus,
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    api_response(false, 'OTP verification failed: ' . $error->getMessage(), [], 500);
}
