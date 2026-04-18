<?php

function current_user()
{
    if (empty($_SESSION['auth_user_id'])) {
        return null;
    }

    $userId = (int) $_SESSION['auth_user_id'];
    $user = db_one('SELECT * FROM users WHERE id = ?', [$userId]);

    if (!$user) {
        logout_user();
        return null;
    }

    if ($user['status'] !== 'active') {
        logout_user();
        return null;
    }

    if (array_key_exists('is_verified', $user) && !$user['is_verified']) {
        logout_user();
        return null;
    }

    if ($user['role'] === 'company') {
        $company = db_one('SELECT * FROM companies WHERE owner_user_id = ?', [$user['id']]);

        if ($company) {
            $user['company_id'] = (int) $company['id'];
            $user['company_name'] = $company['name'];
            $user['company_status'] = $company['status'];
        }
    }

    if ($user['role'] === 'agent') {
        $agent = db_one(
            'SELECT a.id AS agent_id, a.company_id, a.status AS agent_status, c.name AS company_name, c.status AS company_status
             FROM agents a
             INNER JOIN companies c ON c.id = a.company_id
             WHERE a.user_id = ?',
            [$user['id']]
        );

        if ($agent) {
            $user['agent_id'] = (int) $agent['agent_id'];
            $user['company_id'] = (int) $agent['company_id'];
            $user['company_name'] = $agent['company_name'];
            $user['company_status'] = $agent['company_status'];
            $user['agent_status'] = $agent['agent_status'];
        }
    }

    return $user;
}

function login_user($user)
{
    session_regenerate_id(true);
    $_SESSION['auth_user_id'] = (int) $user['id'];
}

function logout_user()
{
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function is_logged_in()
{
    return current_user() !== null;
}

function has_role($roles)
{
    $user = current_user();

    if (!$user) {
        return false;
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    return in_array($user['role'], $roles, true);
}

function require_login()
{
    if (!is_logged_in()) {
        set_flash('Please log in to continue.', 'warning');
        redirect('login.php');
    }
}

function require_role($roles)
{
    require_login();

    if (!has_role($roles)) {
        set_flash('You do not have permission to open that page.', 'danger');
        redirect('dashboard.php');
    }
}

function dashboard_url($section = null)
{
    $defaultSection = 'overview';
    $user = current_user();

    if ($user) {
        $sections = dashboard_sections_for_role($user['role']);
        $keys = array_keys($sections);

        if (!empty($keys[0])) {
            $defaultSection = $keys[0];
        }
    }

    if (!$section) {
        $section = $defaultSection;
    }

    return url('dashboard.php?section=' . urlencode($section));
}

function dashboard_sections_for_role($role)
{
    if ($role === 'super_admin') {
        return [
            'overview' => 'Overview',
            'companies' => 'Companies',
            'users' => 'Users',
            'bookings' => 'Bookings',
        ];
    }

    if ($role === 'company') {
        return [
            'overview' => 'Overview',
            'profile' => 'Company Profile',
            'agents' => 'Agents',
            'vehicles' => 'Vehicles',
            'bookings' => 'Bookings',
        ];
    }

    if ($role === 'agent') {
        return [
            'overview' => 'Overview',
            'vehicles' => 'Vehicles',
            'bookings' => 'Bookings',
        ];
    }

    return [
        'overview' => 'Overview',
        'bookings' => 'My Bookings',
    ];
}

function managed_company_id()
{
    $user = current_user();

    if (!$user || !isset($user['company_id'])) {
        return null;
    }

    return (int) $user['company_id'];
}
