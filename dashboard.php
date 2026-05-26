<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/backend/models/NotificationService.php';

require_login();
ensure_service_history_schema();

$me = current_user();
$pageTitle = 'Dashboard';
$editId = (int) ($_GET['edit'] ?? 0);
$section = $_GET['section'] ?? 'overview';
$selectedBookingId = (int) ($_GET['booking_id'] ?? 0);
$editVehicle = null;
$isPlatformAdmin = is_platform_admin_role($me['role_name']);
$managedCompanyId = managed_company_id($me);

if ($me['role_name'] === 'agent' && $managedCompanyId <= 0) {
    $pageTitle = 'Company assignment required';
    require __DIR__ . '/includes/header.php';
    ?>

    <section class="section-head">
        <h1>Company assignment required</h1>
        <p>Welcome back, <?= e($me['name']) ?>. Your agent account is active, but it is not linked to a company yet.</p>
    </section>

    <section class="box dashboard-panel">
        <h2>Next step</h2>
        <p class="muted">A company owner or platform admin needs to assign this agent account to a company before fleet, booking, maintenance, or payment tools can be used.</p>
        <div class="actions">
            <a class="btn" href="auth/logout.php">Sign out</a>
            <a class="btn light" href="index.php">Back to home</a>
        </div>
    </section>

    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($me['role_name'] === 'agent' && $editId > 0) {
    $editVehicle = db_one('SELECT * FROM vehicles WHERE id = ? AND company_id = ?', [$editId, $managedCompanyId]);
}

$categories = db_all('SELECT * FROM vehicle_categories ORDER BY name');
$types = db_all('SELECT * FROM vehicle_types ORDER BY category_id, name');

$dashboardViews = [
    'user' => __DIR__ . '/dashboard/user.php',
    'company' => __DIR__ . '/dashboard/company.php',
    'agent' => __DIR__ . '/dashboard/agent.php',
    'admin' => __DIR__ . '/dashboard/admin.php',
    'super_admin' => __DIR__ . '/dashboard/admin.php',
];

$dashboardView = $dashboardViews[$me['role_name']] ?? null;

if ($dashboardView === null || !is_file($dashboardView)) {
    flash('Dashboard view is not available for your role.', 'danger');
    go('login.php');
}

require __DIR__ . '/includes/header.php';
?>

<section class="section-head">
    <h1><?= e(ucwords(str_replace('_', ' ', $me['role_name']))) ?> dashboard</h1>
    <p>Welcome back, <?= e($me['name']) ?>.</p>
</section>

<?php if ($section === 'notifications' && $me['role_name'] !== 'user'): ?>
    <?php require __DIR__ . '/dashboard/partials/role_notifications.php'; ?>
<?php endif; ?>

<?php require $dashboardView; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
