<?php

function role_id($role)
{
    return (int) db_value('SELECT id FROM roles WHERE name = ?', [$role]);
}

function find_user($id)
{
    return db_one(
        'SELECT u.*, r.name AS role_name
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.id = ?',
        [$id]
    );
}

function current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return find_user((int) $_SESSION['user_id']);
}

function require_login()
{
    if (!current_user()) {
        flash('Please login first.', 'warning');
        go('login.php');
    }
}

function require_role($role)
{
    require_login();
    $user = current_user();
    $roles = is_array($role) ? $role : [$role];
    if (!role_allowed($user['role_name'], $roles)) {
        flash('You cannot open that page.', 'danger');
        go('dashboard.php');
    }
}

function is_platform_admin_role($role)
{
    return in_array($role, ['admin', 'super_admin'], true);
}

function role_allowed($role, $allowedRoles)
{
    if (in_array($role, $allowedRoles, true)) {
        return true;
    }

    return is_platform_admin_role($role) && (in_array('admin', $allowedRoles, true) || in_array('super_admin', $allowedRoles, true));
}

function company_table_enabled()
{
    return db_table_exists('companies') && db_column_exists('companies', 'owner_user_id');
}

function company_id_for_owner_user($userId)
{
    if (!company_table_enabled()) {
        return (int) $userId;
    }

    return (int) db_value('SELECT id FROM companies WHERE owner_user_id = ? LIMIT 1', [(int) $userId]);
}

function managed_company_id($user)
{
    if (!$user) {
        return 0;
    }

    if ($user['role_name'] === 'company') {
        $companyId = company_id_for_owner_user((int) $user['id']);
        if ($companyId > 0) {
            return $companyId;
        }

        return (int) $user['id'];
    }

    if ($user['role_name'] === 'agent') {
        return (int) ($user['company_id'] ?? 0);
    }

    return 0;
}

function require_company_resource_access($user, $companyId)
{
    $companyId = (int) $companyId;

    if (is_platform_admin_role($user['role_name'])) {
        return $companyId;
    }

    if (managed_company_id($user) !== $companyId) {
        return 0;
    }

    return $companyId;
}
