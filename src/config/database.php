<?php

require_once __DIR__ . '/app.php';

function db()
{
    static $pdo = null;
    static $tried = false;
    static $error = null;

    if ($tried) {
        $GLOBALS['vrs_db_error'] = $error;
        return $pdo;
    }

    $tried = true;

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $error = null;
    } catch (PDOException $e) {
        $pdo = null;
        $error = $e->getMessage();
    }

    $GLOBALS['vrs_db_error'] = $error;
    return $pdo;
}

function require_db()
{
    $pdo = db();

    if (!$pdo) {
        $message = database_error_message();

        if (!$message) {
            $message = 'Unknown database error.';
        }

        throw new RuntimeException('Database connection failed: ' . $message);
    }

    return $pdo;
}

function database_error_message()
{
    db();
    return isset($GLOBALS['vrs_db_error']) ? $GLOBALS['vrs_db_error'] : null;
}
