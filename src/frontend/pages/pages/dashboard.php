<?php

require_once __DIR__ . '/../../../includes/bootstrap.php';

require_login();

$viewer = current_user();
$role = $viewer['role'];
$sections = dashboard_sections_for_role($role);
$section = '';

if (isset($_GET['section'])) {
    $section = (string) $_GET['section'];
}

if ($section === '') {
    foreach ($sections as $sectionKey => $sectionLabel) {
        $section = $sectionKey;
        break;
    }
}

if (!isset($sections[$section])) {
    foreach ($sections as $sectionKey => $sectionLabel) {
        $section = $sectionKey;
        break;
    }
}

$pageTitle = $sections[$section];
$pageDescription = 'Role-based dashboard for the vehicle rental system.';
$companyId = managed_company_id();
$editAgentId = (int) ($_GET['edit_agent'] ?? 0);
$editVehicleId = (int) ($_GET['edit_vehicle'] ?? 0);

$dashboardStats = [];
$userBookings = [];
$companyProfile = null;
$agents = [];
$editAgent = null;
$vehicles = [];
$editVehicle = null;
$companyBookings = [];
$adminCompanies = [];
$adminUsers = [];
$adminBookings = [];

if ($role === 'user') {
    $dashboardStats = [
        'total' => (int) db_value('SELECT COUNT(*) FROM bookings WHERE user_id = ?', [$viewer['id']]),
        'pending' => (int) db_value("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending'", [$viewer['id']]),
        'confirmed' => (int) db_value("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'confirmed'", [$viewer['id']]),
    ];

    $userBookings = db_all('SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC', [$viewer['id']]);

    for ($i = 0; $i < count($userBookings); $i++) {
        $vehicle = db_one('SELECT * FROM vehicles WHERE id = ?', [$userBookings[$i]['vehicle_id']]);
        $company = db_one('SELECT * FROM companies WHERE id = ?', [$userBookings[$i]['company_id']]);
        $documentCount = db_value('SELECT COUNT(*) FROM booking_documents WHERE booking_id = ?', [$userBookings[$i]['id']]);

        $userBookings[$i]['vehicle_name'] = $vehicle ? $vehicle['name'] : 'Unknown vehicle';
        $userBookings[$i]['vehicle_type'] = $vehicle ? $vehicle['type'] : '';
        $userBookings[$i]['company_name'] = $company ? $company['name'] : 'Unknown company';
        $userBookings[$i]['document_count'] = $documentCount ? $documentCount : 0;
    }
}

if (in_array($role, ['company', 'agent'], true) && $companyId) {
    $dashboardStats = [
        'vehicles' => (int) db_value('SELECT COUNT(*) FROM vehicles WHERE company_id = ?', [$companyId]),
        'agents' => (int) db_value('SELECT COUNT(*) FROM agents WHERE company_id = ?', [$companyId]),
        'pending_bookings' => (int) db_value("SELECT COUNT(*) FROM bookings WHERE company_id = ? AND status = 'pending'", [$companyId]),
        'confirmed_bookings' => (int) db_value("SELECT COUNT(*) FROM bookings WHERE company_id = ? AND status = 'confirmed'", [$companyId]),
    ];

    $companyProfile = db_one('SELECT * FROM companies WHERE id = ?', [$companyId]);

    $vehicles = db_all('SELECT * FROM vehicles WHERE company_id = ? ORDER BY created_at DESC', [$companyId]);

    for ($i = 0; $i < count($vehicles); $i++) {
        $vehicles[$i]['image_name'] = db_value(
            'SELECT file_name FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1',
            [$vehicles[$i]['id']]
        );
        $vehicles[$i]['block_count'] = (int) db_value(
            'SELECT COUNT(*) FROM availability_blocks WHERE vehicle_id = ?',
            [$vehicles[$i]['id']]
        );
    }

    $companyBookings = db_all('SELECT * FROM bookings WHERE company_id = ? ORDER BY created_at DESC', [$companyId]);

    for ($i = 0; $i < count($companyBookings); $i++) {
        $bookUser = db_one('SELECT * FROM users WHERE id = ?', [$companyBookings[$i]['user_id']]);
        $bookVehicle = db_one('SELECT * FROM vehicles WHERE id = ?', [$companyBookings[$i]['vehicle_id']]);

        $companyBookings[$i]['user_name'] = $bookUser ? $bookUser['name'] : 'Unknown user';
        $companyBookings[$i]['user_phone'] = $bookUser ? $bookUser['phone'] : '';
        $companyBookings[$i]['vehicle_name'] = $bookVehicle ? $bookVehicle['name'] : 'Unknown vehicle';
        $companyBookings[$i]['handler_agent_id'] = 0;
        $companyBookings[$i]['agent_name'] = '';

        if (!empty($companyBookings[$i]['agent_id'])) {
            $agent = db_one('SELECT * FROM agents WHERE id = ?', [$companyBookings[$i]['agent_id']]);

            if ($agent) {
                $agentUser = db_one('SELECT * FROM users WHERE id = ?', [$agent['user_id']]);
                $companyBookings[$i]['handler_agent_id'] = $agent['id'];

                if ($agentUser) {
                    $companyBookings[$i]['agent_name'] = $agentUser['name'];
                }
            }
        }
    }

    if ($role === 'company') {
        $agents = db_all('SELECT * FROM agents WHERE company_id = ? ORDER BY created_at DESC', [$companyId]);

        for ($i = 0; $i < count($agents); $i++) {
            $agentUser = db_one('SELECT * FROM users WHERE id = ?', [$agents[$i]['user_id']]);

            $agents[$i]['name'] = $agentUser ? $agentUser['name'] : '';
            $agents[$i]['email'] = $agentUser ? $agentUser['email'] : '';
            $agents[$i]['phone'] = $agentUser ? $agentUser['phone'] : '';
        }

        if ($editAgentId > 0) {
            $editAgent = db_one('SELECT * FROM agents WHERE id = ? AND company_id = ?', [$editAgentId, $companyId]);

            if ($editAgent) {
                $editAgentUser = db_one('SELECT * FROM users WHERE id = ?', [$editAgent['user_id']]);

                if ($editAgentUser) {
                    $editAgent['name'] = $editAgentUser['name'];
                    $editAgent['email'] = $editAgentUser['email'];
                    $editAgent['phone'] = $editAgentUser['phone'];
                }
            }
        }
    }

    if ($editVehicleId > 0) {
        $editVehicle = db_one('SELECT * FROM vehicles WHERE id = ? AND company_id = ?', [$editVehicleId, $companyId]);
    }
}

if ($role === 'super_admin') {
    $dashboardStats = [
        'companies' => (int) db_value('SELECT COUNT(*) FROM companies'),
        'approved_companies' => (int) db_value("SELECT COUNT(*) FROM companies WHERE status = 'approved'"),
        'users' => (int) db_value('SELECT COUNT(*) FROM users'),
        'bookings' => (int) db_value('SELECT COUNT(*) FROM bookings'),
    ];

    $adminCompanies = db_all('SELECT * FROM companies ORDER BY created_at DESC');

    for ($i = 0; $i < count($adminCompanies); $i++) {
        $owner = db_one('SELECT * FROM users WHERE id = ?', [$adminCompanies[$i]['owner_user_id']]);

        $adminCompanies[$i]['owner_name'] = $owner ? $owner['name'] : '';
        $adminCompanies[$i]['owner_email'] = $owner ? $owner['email'] : '';
        $adminCompanies[$i]['owner_phone'] = $owner ? $owner['phone'] : '';
    }

    $adminUsers = db_all('SELECT * FROM users ORDER BY created_at DESC');

    $adminBookings = db_all('SELECT * FROM bookings ORDER BY created_at DESC');

    for ($i = 0; $i < count($adminBookings); $i++) {
        $bookUser = db_one('SELECT * FROM users WHERE id = ?', [$adminBookings[$i]['user_id']]);
        $bookCompany = db_one('SELECT * FROM companies WHERE id = ?', [$adminBookings[$i]['company_id']]);
        $bookVehicle = db_one('SELECT * FROM vehicles WHERE id = ?', [$adminBookings[$i]['vehicle_id']]);

        $adminBookings[$i]['user_name'] = $bookUser ? $bookUser['name'] : '';
        $adminBookings[$i]['company_name'] = $bookCompany ? $bookCompany['name'] : '';
        $adminBookings[$i]['vehicle_name'] = $bookVehicle ? $bookVehicle['name'] : '';
    }
}

require __DIR__ . '/../../../includes/header.php';
?>

<section class="container section">
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar stacked-panel">
            <span class="eyebrow"><?= e(ucfirst(str_replace('_', ' ', $role))) ?> Dashboard</span>
            <h1><?= e($viewer['name']) ?></h1>
            <p><?= e($viewer['email'] ?: $viewer['phone']) ?></p>

            <nav class="dashboard-nav">
                <?php foreach ($sections as $sectionKey => $sectionLabel): ?>
                    <a href="<?= e(url('dashboard.php?section=' . $sectionKey)) ?>" class="<?= $section === $sectionKey ? 'is-active' : '' ?>">
                        <?= e($sectionLabel) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="dashboard-content">
            <section class="metric-grid">
                <?php foreach ($dashboardStats as $label => $value): ?>
                    <article class="metric-card">
                        <strong><?= e((string) $value) ?></strong>
                        <span><?= e(ucwords(str_replace('_', ' ', $label))) ?></span>
                    </article>
                <?php endforeach; ?>
            </section>

            <?php
            if ($role === 'user') {
                require __DIR__ . '/dashboard/user.php';
            } elseif (in_array($role, ['company', 'agent'], true)) {
                require __DIR__ . '/dashboard/company.php';
            } elseif ($role === 'super_admin') {
                require __DIR__ . '/dashboard/admin.php';
            }
            ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>


