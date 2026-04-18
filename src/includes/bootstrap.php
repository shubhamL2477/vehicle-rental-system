<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

ensure_upload_directories();

$GLOBALS['flash'] = pull_flash();
$GLOBALS['old_input'] = pull_old_input();

require_once __DIR__ . '/auth.php';
