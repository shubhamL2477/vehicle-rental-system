USE vehicle_rental_system;

ALTER TABLE users
ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL AFTER is_verified;

ALTER TABLE otp_codes
ADD COLUMN IF NOT EXISTS purpose ENUM('account_verification', 'password_reset')
NOT NULL DEFAULT 'account_verification' AFTER otp_code;

ALTER TABLE otp_codes
ADD COLUMN IF NOT EXISTS otp_expires_at DATETIME NULL AFTER purpose,
ADD COLUMN IF NOT EXISTS otp_last_sent_at DATETIME NULL AFTER otp_expires_at;

UPDATE otp_codes
SET otp_expires_at = COALESCE(otp_expires_at, expires_at),
    otp_last_sent_at = COALESCE(otp_last_sent_at, created_at);

UPDATE users
SET is_verified = 1,
    verified_at = NOW()
WHERE is_verified = 0;
