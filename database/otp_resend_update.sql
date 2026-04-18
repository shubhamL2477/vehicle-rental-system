USE vehicle_rental_system;

ALTER TABLE otp_codes
ADD COLUMN IF NOT EXISTS otp_expires_at DATETIME NULL AFTER purpose,
ADD COLUMN IF NOT EXISTS otp_last_sent_at DATETIME NULL AFTER otp_expires_at;

UPDATE otp_codes
SET otp_expires_at = COALESCE(otp_expires_at, expires_at),
    otp_last_sent_at = COALESCE(otp_last_sent_at, created_at);
