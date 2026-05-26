<?php
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
if (!$user) {
    flash('Please login first.', 'warning');
    go('../login.php');
}

if (!role_allowed($user['role_name'], ['company', 'agent'])) {
    flash('You cannot open that page.', 'danger');
    go('../dashboard.php');
}

go('../dashboard.php?section=overview');
