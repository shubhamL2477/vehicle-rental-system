<?php
// shared helper for API files that need a logged-in user
require_once __DIR__ . '/functions.php';

function auth_check_user($roles = [], $allowSession = true)
{
    $user = api_token_user();

    if (!$user && $allowSession) {
        $user = current_user();
    }

    if (!$user) {
        return null;
    }

    if ($roles && !role_allowed($user['role_name'], $roles)) {
        return null;
    }

    return $user;
}
