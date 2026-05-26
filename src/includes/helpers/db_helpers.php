<?php

function db_all($sql, $params = [])
{
    $pdo = db();

    if (!$pdo) {
        return [];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_one($sql, $params = [])
{
    $pdo = db();

    if (!$pdo) {
        return null;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    return $row;
}

function db_value($sql, $params = [])
{
    $row = db_one($sql, $params);

    if (!$row) {
        return null;
    }

    foreach ($row as $value) {
        return $value;
    }

    return null;
}

function role_id_by_name($roleName)
{
    $roleId = (int) db_value('SELECT id FROM roles WHERE name = ?', [$roleName]);

    if ($roleId > 0) {
        return $roleId;
    }

    if ($roleName === 'super_admin') {
        return 1;
    }

    if ($roleName === 'company') {
        return 2;
    }

    if ($roleName === 'agent') {
        return 3;
    }

    return 4;
}
