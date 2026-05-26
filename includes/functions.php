<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/email.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers/core_helpers.php';
require_once __DIR__ . '/helpers/schema_helpers.php';
require_once __DIR__ . '/helpers/auth_helpers.php';
require_once __DIR__ . '/helpers/vehicle_helpers.php';
require_once __DIR__ . '/helpers/payment_helpers.php';
require_once __DIR__ . '/helpers/review_helpers.php';
require_once __DIR__ . '/helpers/otp_helpers.php';
