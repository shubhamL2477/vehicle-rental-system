<?php

require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(false, 'Only POST method is allowed.', array(), 405);
}

$data = get_request_data();

$credential = isset($data['credential']) ? trim($data['credential']) : '';
$otp_code = isset($data['otp_code']) ? trim($data['otp_code']) : '';

if ($credential === '' || $otp_code === '') {
    send_json(false, 'Credential and OTP are required.', array(), 422);
}

$user = find_user_by_credential($credential);

if (!$user) {
    send_json(false, 'User not found.', array(), 404);
}

$pdo = get_db();
$stmt = $pdo->prepare(
    'SELECT * FROM otp_codes
     WHERE user_id = ? AND otp_code = ? AND is_used = 0
     ORDER BY id DESC
     LIMIT 1'
);
$stmt->execute(array($user['id'], $otp_code));
$otp = $stmt->fetch();

if (!$otp) {
    send_json(false, 'Invalid OTP.', array(), 422);
}

if (strtotime($otp['otp_expires_at']) < time()) {
    send_json(false, 'OTP expired.', array(), 422);
}

$update_user = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
$update_user->execute(array('active', $user['id']));

$update_otp = $pdo->prepare('UPDATE otp_codes SET is_used = 1 WHERE id = ?');
$update_otp->execute(array($otp['id']));

send_json(true, 'OTP verified. Account is now active.', array(
    'user_id' => (int) $user['id'],
    'status' => 'active'
));
