<?php

function ensure_otp_schema()
{
    if (!db_table_exists('otp_codes')) {
        return;
    }

    if (!db_column_exists('otp_codes', 'purpose')) {
        try {
            db_run("ALTER TABLE otp_codes ADD COLUMN purpose ENUM('verify', 'reset') NOT NULL DEFAULT 'verify' AFTER otp_code");
        } catch (Throwable $throwable) {
            if (stripos($throwable->getMessage(), 'Duplicate column') === false) {
                throw $throwable;
            }
        }
    }

    $purposeColumn = db_one('SHOW COLUMNS FROM otp_codes LIKE "purpose"');
    if ($purposeColumn && stripos((string) ($purposeColumn['Type'] ?? ''), "'verify'") === false) {
        db_run("ALTER TABLE otp_codes MODIFY COLUMN purpose ENUM('verify', 'reset', 'account_verification', 'password_reset') NOT NULL DEFAULT 'verify'");
    }

    if (!db_column_exists('otp_codes', 'expires_at')) {
        try {
            db_run('ALTER TABLE otp_codes ADD COLUMN expires_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER purpose');
        } catch (Throwable $throwable) {
            if (stripos($throwable->getMessage(), 'Duplicate column') === false) {
                throw $throwable;
            }
        }
    }
    if (db_column_exists('otp_codes', 'otp_expires_at')) {
        db_run('UPDATE otp_codes SET expires_at = otp_expires_at WHERE expires_at IS NULL AND otp_expires_at IS NOT NULL');
    }

    if (!db_column_exists('otp_codes', 'last_sent_at')) {
        try {
            db_run('ALTER TABLE otp_codes ADD COLUMN last_sent_at DATETIME NULL AFTER expires_at');
        } catch (Throwable $throwable) {
            if (stripos($throwable->getMessage(), 'Duplicate column') === false) {
                throw $throwable;
            }
        }
        $fallback = db_column_exists('otp_codes', 'created_at') ? 'COALESCE(created_at, NOW())' : 'NOW()';
        db_run('UPDATE otp_codes SET last_sent_at = ' . $fallback . ' WHERE last_sent_at IS NULL');
    }
    if (db_column_exists('otp_codes', 'otp_last_sent_at')) {
        db_run('UPDATE otp_codes SET last_sent_at = otp_last_sent_at WHERE last_sent_at IS NULL AND otp_last_sent_at IS NOT NULL');
    }

    if (!db_column_exists('otp_codes', 'is_used')) {
        try {
            db_run('ALTER TABLE otp_codes ADD COLUMN is_used TINYINT(1) NOT NULL DEFAULT 0 AFTER last_sent_at');
        } catch (Throwable $throwable) {
            if (stripos($throwable->getMessage(), 'Duplicate column') === false) {
                throw $throwable;
            }
        }
    }

    if (!db_column_exists('otp_codes', 'used')) {
        try {
            db_run('ALTER TABLE otp_codes ADD COLUMN used TINYINT(1) NOT NULL DEFAULT 0 AFTER is_used');
        } catch (Throwable $throwable) {
            if (stripos($throwable->getMessage(), 'Duplicate column') === false) {
                throw $throwable;
            }
        }
    }

    if (db_column_exists('otp_codes', 'used')) {
        db_run('UPDATE otp_codes SET used = is_used WHERE used <> is_used');
    }
}

function otp_purpose_values($purpose)
{
    if ($purpose === 'reset') {
        return ['reset', 'password_reset'];
    }

    return ['verify', 'account_verification'];
}

function create_otp($userId, $purpose)
{
    ensure_otp_schema();
    $purposeValues = otp_purpose_values($purpose);

    $last = db_one(
        'SELECT * FROM otp_codes WHERE user_id = ? AND purpose IN (?, ?) ORDER BY id DESC LIMIT 1',
        [$userId, $purposeValues[0], $purposeValues[1]]
    );

    if ($last && strtotime($last['last_sent_at']) > time() - OTP_RESEND_SECONDS) {
        return ['error' => 'Please wait before resending OTP.'];
    }

    $code = (string) random_int(100000, 999999);
    $expires = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRE_MINUTES . ' minutes'));

    db_run(
        'INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at, last_sent_at)
         VALUES (?, ?, ?, ?, NOW())',
        [$userId, $code, $purpose, $expires]
    );

    return ['code' => $code, 'expires_at' => $expires];
}

function verify_otp_code($userId, $code, $purpose)
{
    ensure_otp_schema();
    $purposeValues = otp_purpose_values($purpose);

    $otp = db_one(
        'SELECT * FROM otp_codes
         WHERE user_id = ? AND otp_code = ? AND purpose IN (?, ?) AND is_used = 0 AND expires_at >= NOW()
         ORDER BY id DESC LIMIT 1',
        [$userId, $code, $purposeValues[0], $purposeValues[1]]
    );

    if (!$otp) {
        return false;
    }

    if (db_column_exists('otp_codes', 'used')) {
        db_run('UPDATE otp_codes SET is_used = 1, used = 1 WHERE id = ?', [$otp['id']]);
    } else {
        db_run('UPDATE otp_codes SET is_used = 1 WHERE id = ?', [$otp['id']]);
    }
    return true;
}
