<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

function auth_user_columns($refresh = false)
{
    $pdo = db();

    if (!$pdo) {
        return [];
    }

    try {
        $rows = $pdo->query('SHOW COLUMNS FROM users')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $columns = [];

    foreach ($rows as $row) {
        if (!empty($row['Field'])) {
            $columns[] = $row['Field'];
        }
    }

    return $columns;
}

function auth_has_user_column($columnName)
{
    return in_array($columnName, auth_user_columns(), true);
}

function auth_ensure_verification_columns()
{
    $pdo = require_db();
    $updated = false;

    if (!auth_has_user_column('is_verified')) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER status'
        );
        $updated = true;
    }

    if (!auth_has_user_column('verified_at')) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN verified_at DATETIME NULL AFTER is_verified'
        );
        $updated = true;
    }

    if ($updated) {
        auth_user_columns(true);
    }

    $pdo->exec(
        "UPDATE users
         SET status = 'active',
             verified_at = COALESCE(verified_at, NOW())
         WHERE is_verified = 1 AND status <> 'active'"
    );
}

auth_ensure_verification_columns();

function auth_find_user_by_email($email)
{
    return db_one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
}

function auth_is_verified($user)
{
    return !empty($user['is_verified']);
}

function auth_mark_user_verified($userId)
{
    $sql = 'UPDATE users
            SET is_verified = 1, verified_at = NOW(), status = ?
            WHERE id = ?';

    require_db()->prepare($sql)->execute(['active', $userId]);
}
