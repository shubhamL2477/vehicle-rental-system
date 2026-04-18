<?php

require_once __DIR__ . '/../../includes/session.php';

logout_user();

if (request_method() === 'POST') {
    json_response(true, 'Logout successful.', [
        'redirect_url' => app_url('signinpage.html?logout=1'),
    ]);
}

redirect_to('signinpage.html?logout=1');
