<?php

const OTP_EXPIRY_SECONDS = 60;
const OTP_RESEND_COOLDOWN_SECONDS = 60;

function otp_columns()
{
    $pdo = db();

    if (!$pdo) {
        return [];
    }

    try {
        $rows = $pdo->query('SHOW COLUMNS FROM otp_codes')->fetchAll();
    } catch (Throwable $error) {
        return [];
    }

    $columns = [];

    foreach ($rows as $row) {
        $field = (string) ($row['Field'] ?? '');

        if ($field !== '') {
            $columns[] = $field;
        }
    }

    return $columns;
}

function otp_has_column($columnName)
{
    $columns = otp_columns();

    foreach ($columns as $column) {
        if ($column === $columnName) {
            return true;
        }
    }

    return false;
}

function otp_has_purpose_column()
{
    return otp_has_column('purpose');
}

function otp_ensure_columns()
{
    $pdo = db();

    if (!$pdo) {
        return;
    }

    $updated = false;

    if (!otp_has_column('purpose')) {
        $pdo->exec(
            "ALTER TABLE otp_codes
             ADD COLUMN purpose ENUM('account_verification', 'password_reset')
             NOT NULL DEFAULT 'account_verification' AFTER otp_code"
        );
        $updated = true;
    }

    if (!otp_has_column('otp_expires_at')) {
        $pdo->exec(
            'ALTER TABLE otp_codes
             ADD COLUMN otp_expires_at DATETIME NULL AFTER purpose'
        );
        $updated = true;
    }

    if (!otp_has_column('otp_last_sent_at')) {
        $pdo->exec(
            'ALTER TABLE otp_codes
             ADD COLUMN otp_last_sent_at DATETIME NULL AFTER otp_expires_at'
        );
        $updated = true;
    }

    if ($updated) {
        $pdo->exec(
            'UPDATE otp_codes
             SET otp_expires_at = COALESCE(otp_expires_at, expires_at),
                 otp_last_sent_at = COALESCE(otp_last_sent_at, created_at)'
        );
    }
}

function otp_expires_at($otp)
{
    if (isset($otp['otp_expires_at']) && $otp['otp_expires_at'] !== '') {
        return $otp['otp_expires_at'];
    }

    if (isset($otp['expires_at'])) {
        return $otp['expires_at'];
    }

    return '';
}

function otp_last_sent_at($otp)
{
    if (isset($otp['otp_last_sent_at']) && $otp['otp_last_sent_at'] !== '') {
        return $otp['otp_last_sent_at'];
    }

    if (isset($otp['created_at'])) {
        return $otp['created_at'];
    }

    return '';
}

function generate_otp_code()
{
    return (string) random_int(100000, 999999);
}

function clear_unused_otps($userId, $purpose)
{
    if (otp_has_purpose_column()) {
        require_db()->prepare(
            'UPDATE otp_codes
             SET is_used = 1
             WHERE user_id = ? AND purpose = ? AND is_used = 0'
        )->execute([$userId, $purpose]);

        return;
    }

    require_db()->prepare(
        'UPDATE otp_codes
         SET is_used = 1
         WHERE user_id = ? AND is_used = 0'
    )->execute([$userId]);
}

function create_otp($userId, $purpose)
{
    $otpCode = generate_otp_code();
    $expiresAt = date('Y-m-d H:i:s', time() + OTP_EXPIRY_SECONDS);
    $lastSentAt = date('Y-m-d H:i:s');
    clear_unused_otps($userId, $purpose);

    if (otp_has_purpose_column()) {
        if (otp_has_column('otp_expires_at') && otp_has_column('otp_last_sent_at')) {
            require_db()->prepare(
                'INSERT INTO otp_codes (user_id, otp_code, purpose, otp_expires_at, otp_last_sent_at, expires_at, is_used)
                 VALUES (?, ?, ?, ?, ?, ?, 0)'
            )->execute([$userId, $otpCode, $purpose, $expiresAt, $lastSentAt, $expiresAt]);
        } else {
            require_db()->prepare(
                'INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at, is_used)
                 VALUES (?, ?, ?, ?, 0)'
            )->execute([$userId, $otpCode, $purpose, $expiresAt]);
        }
    } else {
        require_db()->prepare(
            'INSERT INTO otp_codes (user_id, otp_code, expires_at, is_used)
             VALUES (?, ?, ?, 0)'
        )->execute([$userId, $otpCode, $expiresAt]);
    }

    return [
        'otp_code' => $otpCode,
        'otp_expires_at' => $expiresAt,
        'otp_last_sent_at' => $lastSentAt,
    ];
}

function latest_sent_otp($userId, $purpose)
{
    if (otp_has_purpose_column()) {
        return db_one(
            'SELECT * FROM otp_codes
             WHERE user_id = ? AND purpose = ?
             ORDER BY id DESC
             LIMIT 1',
            [$userId, $purpose]
        );
    }

    return db_one(
        'SELECT * FROM otp_codes
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 1',
        [$userId]
    );
}

function latest_active_otp($userId, $purpose)
{
    $otp = null;

    if (otp_has_purpose_column()) {
        $otp = db_one(
            'SELECT * FROM otp_codes
             WHERE user_id = ? AND purpose = ? AND is_used = 0
             ORDER BY id DESC
             LIMIT 1',
            [$userId, $purpose]
        );
    } else {
        $otp = db_one(
            'SELECT * FROM otp_codes
             WHERE user_id = ? AND is_used = 0
             ORDER BY id DESC
             LIMIT 1',
            [$userId]
        );
    }

    if (!$otp) {
        return null;
    }

    if (strtotime(otp_expires_at($otp)) <= time()) {
        return null;
    }

    return $otp;
}

function otp_seconds_left($otp)
{
    $secondsLeft = strtotime(otp_expires_at($otp)) - time();
    return max(0, $secondsLeft);
}

function otp_resend_seconds_left($otp)
{
    $secondsLeft = strtotime(otp_last_sent_at($otp)) + OTP_RESEND_COOLDOWN_SECONDS - time();
    return max(0, $secondsLeft);
}

function find_valid_otp($userId, $otpCode, $purpose)
{
    $otp = null;

    if (otp_has_purpose_column()) {
        $otp = db_one(
            'SELECT * FROM otp_codes
             WHERE user_id = ? AND otp_code = ? AND purpose = ? AND is_used = 0
             ORDER BY id DESC
             LIMIT 1',
            [$userId, $otpCode, $purpose]
        );
    } else {
        $otp = db_one(
            'SELECT * FROM otp_codes
             WHERE user_id = ? AND otp_code = ? AND is_used = 0
             ORDER BY id DESC
             LIMIT 1',
            [$userId, $otpCode]
        );
    }

    if (!$otp) {
        return null;
    }

    if (strtotime(otp_expires_at($otp)) <= time()) {
        return null;
    }

    return $otp;
}

function mark_otp_used($otpId)
{
    if (otp_has_column('otp_expires_at')) {
        require_db()->prepare(
            "UPDATE otp_codes
             SET is_used = 1,
                 otp_code = '',
                 otp_expires_at = NOW(),
                 expires_at = NOW()
             WHERE id = ?"
        )->execute([$otpId]);

        return;
    }

    require_db()->prepare(
        "UPDATE otp_codes
         SET is_used = 1,
             otp_code = '',
             expires_at = NOW()
         WHERE id = ?"
    )->execute([$otpId]);
}

otp_ensure_columns();
