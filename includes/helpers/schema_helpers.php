<?php

function db_table_exists($table)
{
    static $cache = [];
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);

    if ($table === '') {
        return false;
    }

    if (!array_key_exists($table, $cache)) {
        try {
            $cache[$table] = db_value('SHOW TABLES LIKE ?', [$table]) !== null;
        } catch (Throwable $throwable) {
            $cache[$table] = false;
        }
    }

    return $cache[$table];
}

function db_column_exists($table, $column)
{
    static $cache = [];
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
    $key = $table . '.' . $column;

    if ($table === '' || $column === '') {
        return false;
    }

    if (!array_key_exists($key, $cache)) {
        try {
            $cache[$key] = db_one('SHOW COLUMNS FROM `' . $table . '` LIKE ?', [$column]) !== null;
        } catch (Throwable $throwable) {
            $cache[$key] = false;
        }
    }

    return $cache[$key];
}

function db_enum_allows($table, $column, $value)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);

    if ($table === '' || $column === '') {
        return false;
    }

    $definition = db_one('SHOW COLUMNS FROM `' . $table . '` LIKE ?', [$column]);
    if (!$definition) {
        return false;
    }

    return stripos((string) ($definition['Type'] ?? ''), "'" . str_replace("'", "''", (string) $value) . "'") !== false;
}

function ensure_service_history_schema()
{
    if (db_table_exists('service_history')) {
        return;
    }

    db_run(
        'CREATE TABLE IF NOT EXISTS service_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT UNSIGNED NOT NULL,
            company_id INT UNSIGNED NOT NULL,
            service_type VARCHAR(120) NOT NULL,
            provider VARCHAR(120) NULL,
            mileage INT NULL,
            cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            service_date DATE NOT NULL,
            notes TEXT NULL,
            created_by_user_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_service_history_vehicle_date (vehicle_id, service_date),
            INDEX idx_service_history_company_date (company_id, service_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}
