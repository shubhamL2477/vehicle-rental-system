<?php
require_once __DIR__ . '/config.php';

function db()
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $ports = ['3306', '3307', '3308'];
    $lastException = null;

    foreach ($ports as $port) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            return $pdo;
        } catch (PDOException $exception) {
            $lastException = $exception;
        }
    }

    throw $lastException ?: new PDOException('Database connection failed.');
}

function db_all($sql, $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_one($sql, $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ? $row : null;
}

function db_value($sql, $params = [])
{
    $row = db_one($sql, $params);
    if (!$row) {
        return null;
    }
    return array_values($row)[0];
}

function db_run($sql, $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_log_error($exception, $context = '')
{
    $folder = APP_ROOT . '/logs';

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $message = '[' . date('Y-m-d H:i:s') . ']';
    if ($context !== '') {
        $message .= ' ' . $context . ':';
    }
    $message .= ' ' . $exception->getMessage() . PHP_EOL;

    error_log($message, 3, $folder . '/db_errors.log');
}
