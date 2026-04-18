<?php

require_once __DIR__ . '/../../includes/session.php';

$user = current_user();

if (!$user) {
    json_response(false, 'No authenticated user.', [], 401);
}

json_response(true, 'Authenticated user found.', [
    'user' => $user,
]);
