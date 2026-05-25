<?php
require_once __DIR__ . '/../backend/models/NotificationService.php';
$pageTitle = $pageTitle ?? APP_NAME;
$me = current_user();
$msg = get_flash();
$unreadNotifications = $me ? NotificationService::unreadCount((int) $me['id']) : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>
    <base href="<?= e(APP_PUBLIC_URL) ?>/">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php">
        <img src="assets/images/logo.png" alt="Hyrox Rental">
        <span><?= APP_NAME ?></span>
    </a>
    <p class="tagline"><?= APP_TAGLINE ?></p>
    <nav>
        <a href="vehicles.php">Vehicles</a>
        <?php if ($me): ?>
            <a href="dashboard.php">Dashboard</a>
            <a class="notification-bell" href="dashboard.php?section=notifications" aria-label="Notifications">
                Notifications
                <?php if ($unreadNotifications > 0): ?>
                    <span><?= e($unreadNotifications) ?></span>
                <?php endif; ?>
            </a>
            <a href="payments.php">Payments</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<?php if ($msg): ?>
    <div class="alert <?= e($msg['type']) ?>"><?= e($msg['message']) ?></div>
<?php endif; ?>

<main>
