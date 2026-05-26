<?php
require_once __DIR__ . '/../backend/models/NotificationService.php';
$pageTitle = $pageTitle ?? APP_NAME;
$me = current_user();
$msg = get_flash();
$unreadNotifications = $me ? NotificationService::unreadCount((int) $me['id']) : 0;
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$bodyClass = 'page-' . preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($currentPage, PATHINFO_FILENAME)));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>
    <base href="<?= e(APP_PUBLIC_URL) ?>/">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= e((string) filemtime(__DIR__ . '/../assets/css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<header class="topbar">
    <a class="brand" href="index.php">
        <span class="brand-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="img" focusable="false">
                <path d="M5 15.5h1.1a2.3 2.3 0 0 0 4.5 0h3a2.3 2.3 0 0 0 4.5 0H19a1.7 1.7 0 0 0 1.7-1.7v-2.1c0-.7-.4-1.3-1-1.5l-2.4-.9-2.1-2.7A2.7 2.7 0 0 0 13.1 5H8.4a2.8 2.8 0 0 0-2.5 1.6L4 10.4a3.6 3.6 0 0 0-.4 1.6v1.8A1.5 1.5 0 0 0 5 15.5Zm2.2 0a1.1 1.1 0 1 1 2.2 0 1.1 1.1 0 0 1-2.2 0Zm7.6 0a1.1 1.1 0 1 1 2.2 0 1.1 1.1 0 0 1-2.2 0ZM7.2 9.8l1.1-2.1c.2-.4.7-.7 1.1-.7H13c.4 0 .8.2 1 .5l1.8 2.3H7.2Z" fill="currentColor"/>
            </svg>
        </span>
        <span class="brand-word"><?= APP_NAME ?></span>
    </a>
    <p class="tagline"><?= APP_TAGLINE ?></p>
    <nav class="site-nav">
        <a class="<?= $currentPage === 'vehicles.php' || $currentPage === 'vehicle.php' ? 'active' : '' ?>" href="vehicles.php">Vehicles</a>
        <?php if ($me): ?>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
            <a class="notification-bell" href="dashboard.php?section=notifications" aria-label="Notifications">
                Notifications
                <?php if ($unreadNotifications > 0): ?>
                    <span><?= e($unreadNotifications) ?></span>
                <?php endif; ?>
            </a>
            <a class="<?= $currentPage === 'payments.php' || $currentPage === 'payment-status.php' ? 'active' : '' ?>" href="payments.php">Payments</a>
            <span class="nav-role"><?= e(ucwords(str_replace('_', ' ', $me['role_name']))) ?></span>
            <a class="nav-action" href="auth/logout.php" data-logout-link>Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a class="nav-action" href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<?php if ($msg): ?>
    <div class="alert <?= e($msg['type']) ?>"><?= e($msg['message']) ?></div>
<?php endif; ?>

<main>
