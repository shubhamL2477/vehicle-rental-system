<?php

date_default_timezone_set('Asia/Kathmandu');

function env_value($key, $default = '')
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

define('APP_NAME', 'Hyrox Rental');
define('APP_TAGLINE', 'Reserve verified vehicles across trusted companies.');
define('APP_ROOT', dirname(__DIR__));
define('APP_PUBLIC_URL', rtrim(env_value('APP_PUBLIC_URL', 'http://localhost/vehicle-rental-system-clean'), '/'));

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'vehicle_rental');
define('DB_USER', 'root');
define('DB_PASS', '');

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', env_value('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env_value('MAIL_PASSWORD', ''));
define('MAIL_FROM', env_value('MAIL_FROM', MAIL_USERNAME));

define('JWT_SECRET', env_value('JWT_SECRET', 'change-this-demo-secret'));
define('JWT_EXPIRE_SECONDS', 900);
define('REFRESH_EXPIRE_DAYS', 7);

define('STRIPE_SECRET_KEY', env_value('STRIPE_SECRET_KEY', ''));
define('STRIPE_WEBHOOK_SECRET', env_value('STRIPE_WEBHOOK_SECRET', ''));
define('STRIPE_CURRENCY', strtolower(env_value('STRIPE_CURRENCY', 'npr')));
define('STRIPE_API_VERSION', env_value('STRIPE_API_VERSION', '2026-02-25.clover'));

define('OTP_EXPIRE_MINUTES', 5);
define('OTP_RESEND_SECONDS', 60);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

define('VEHICLE_UPLOAD_PATH', APP_ROOT . '/uploads/vehicles/');
define('DOCUMENT_UPLOAD_PATH', APP_ROOT . '/uploads/documents/');
