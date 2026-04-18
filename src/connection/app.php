<?php

function load_env_file($path)
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $separator = strpos($line, '=');

        if ($separator === false) {
            continue;
        }

        $name = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));

        if ($name === '') {
            continue;
        }

        if (strlen($value) >= 2) {
            $firstCharacter = $value[0];
            $lastCharacter = $value[strlen($value) - 1];

            if ($firstCharacter === '"' && $lastCharacter === '"') {
                $value = stripcslashes(substr($value, 1, -1));
            } elseif ($firstCharacter === "'" && $lastCharacter === "'") {
                $value = substr($value, 1, -1);
            }
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function env_value($key, $default = '')
{
    $value = getenv($key);

    if ($value !== false && $value !== '') {
        return $value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }

    return $default;
}

$projectRoot = dirname(__DIR__, 2);
load_env_file($projectRoot . '/.env');

date_default_timezone_set(env_value('APP_TIMEZONE', 'Asia/Kathmandu'));

define('APP_NAME', env_value('APP_NAME', 'Vehicle Rental System'));
define('APP_TAGLINE', env_value('APP_TAGLINE', 'Reserve verified vehicles across trusted companies.'));

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME', 'vehicle_rental_system'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));

// Mail settings are loaded from environment variables so secrets stay out of git.
define('MAIL_HOST', env_value('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int) env_value('MAIL_PORT', '587'));
define('MAIL_USERNAME', env_value('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env_value('MAIL_PASSWORD', ''));
define('MAIL_FROM_EMAIL', env_value('MAIL_FROM_EMAIL', ''));
define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', 'Vehicle Rental System'));
define('MAIL_ENCRYPTION', env_value('MAIL_ENCRYPTION', 'tls'));

define('MAX_UPLOAD_SIZE', 5_242_880);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_DOCUMENT_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('USER_ROLES', ['super_admin', 'company', 'agent', 'user']);
define('BOOKING_STATUSES', ['pending', 'confirmed', 'cancelled']);
define('COMPANY_STATUSES', ['pending', 'approved', 'rejected']);
define('ACCOUNT_STATUSES', ['active', 'inactive', 'pending']);
define('VEHICLE_STATUSES', ['available', 'unavailable', 'maintenance']);
define('VEHICLE_TYPES', ['car', 'bike', 'bus', 'van', 'jeep', 'suv', 'scooter']);

define('VEHICLE_UPLOAD_DIR', 'public/assets/uploads/vehicles');
define('DOCUMENT_UPLOAD_DIR', 'public/assets/uploads/documents');
define('APP_ROOT', dirname(__DIR__));
