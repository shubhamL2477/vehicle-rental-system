<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../backend/models/NotificationService.php';

check_csrf();
require_login();

$me = current_user();
$action = $_POST['action'] ?? '';

if ($action === 'mark_read') {
    NotificationService::markRead((int) ($_POST['notification_id'] ?? 0), (int) $me['id']);
    go('../dashboard.php?section=notifications');
}

if ($action === 'mark_all_read') {
    NotificationService::markAllRead((int) $me['id']);
    go('../dashboard.php?section=notifications');
}

go('../dashboard.php');
