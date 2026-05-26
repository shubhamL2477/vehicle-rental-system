<?php
require_once __DIR__ . '/../../backend/routes/api_common.php';

try {
    api_require_method('POST');

    $data = api_data();
    $userId = (int) ($data['user_id'] ?? ($_SESSION['verify_user_id'] ?? 0));
    $code = trim((string) ($data['otp_code'] ?? $data['otp'] ?? ''));

    if ($userId < 1 || $code === '') {
        api_response(false, 'user_id and otp_code are required.', [], 422);
    }

    if (!verify_otp_code($userId, $code, 'verify')) {
        api_response(false, 'OTP is wrong, expired, or already used.', [], 422);
    }

    $user = find_user($userId);
    if (!$user) {
        api_response(false, 'User not found.', [], 404);
    }

    $status = $user['role_name'] === 'company' ? 'pending_admin' : 'active';
    if (!db_enum_allows('users', 'status', $status)) {
        $status = $user['role_name'] === 'company' ? 'pending' : 'active';
    }

    db_run('UPDATE users SET is_verified = 1, status = ? WHERE id = ?', [$status, $userId]);
    unset($_SESSION['verify_user_id']);

    api_response(true, 'OTP verified. Account activated.', [
        'user_id' => $userId,
        'status' => $status,
    ]);
} catch (Throwable $throwable) {
    api_response(false, 'Unable to verify OTP: ' . $throwable->getMessage(), [], 500);
}
