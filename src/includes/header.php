<?php

$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? APP_TAGLINE;
$activePath = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
$viewer = current_user();
$flash = $GLOBALS['flash'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
</head>
<body>
<div class="site-bg"></div>
<header class="site-header">
    <div class="header-shell header-inner">
        <a class="brand" href="<?= e(url('index.php')) ?>">
            <img src="<?= e(asset_url('images/hyrox-logo.png')) ?>" alt="<?= e(APP_NAME) ?>" class="brand-logo">
            <span class="brand-copy">
                <small><?= e(APP_TAGLINE) ?></small>
            </span>
        </a>
        <nav class="main-nav">
            <a href="<?= e(url('index.php')) ?>" class="<?= $activePath === 'index.php' ? 'is-active' : '' ?>">Home</a>
            <a href="<?= e(url('vehicles.php')) ?>" class="<?= $activePath === 'vehicles.php' ? 'is-active' : '' ?>">Vehicles</a>
            <?php if ($viewer): ?>
                <a href="<?= e(dashboard_url()) ?>" class="<?= $activePath === 'dashboard.php' ? 'is-active' : '' ?>">Dashboard</a>
                <a href="<?= e(url('logout.php')) ?>">Logout</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>" class="<?= $activePath === 'login.php' ? 'is-active' : '' ?>">Login</a>
                <a href="<?= e(url('register.php')) ?>" class="nav-cta">Get Started</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if (database_error_message()): ?>
    <section class="container setup-alert">
        <div class="alert alert-danger">
            <strong>Database connection needed.</strong>
            Import the files in <code>src/database/schema.sql</code> and <code>src/database/seed.sql</code>, then update credentials in <code>src/config/app.php</code> if required.
        </div>
    </section>
<?php endif; ?>

<?php if ($flash): ?>
    <section class="container flash-stack">
        <div class="alert alert-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    </section>
<?php endif; ?>

<main class="page-shell">
