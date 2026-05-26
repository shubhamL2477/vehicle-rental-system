<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$companyId = (int) ($_GET['id'] ?? 0);
$company = db_one('SELECT * FROM companies WHERE id = ? AND status = ?', [$companyId, 'approved']);

if (!$company) {
    http_response_code(404);
    $pageTitle = 'Company not found';
    require __DIR__ . '/../includes/header.php';
    ?>
    <section class="container section">
        <div class="empty-state">
            <h1>Company not found</h1>
            <p>The company profile you requested is unavailable.</p>
            <a class="button button-primary" href="<?= e(url('vehicles.php')) ?>">Back to listings</a>
        </div>
    </section>
    <?php
    require __DIR__ . '/../includes/footer.php';
    return;
}

$pageTitle = $company['name'];
$pageDescription = 'View company details and the vehicles listed under this rental provider.';

$vehicles = db_all('SELECT * FROM vehicles WHERE company_id = ? ORDER BY created_at DESC', [$companyId]);

for ($i = 0; $i < count($vehicles); $i++) {
    $vehicles[$i]['image_name'] = db_value(
        'SELECT file_name FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1',
        [$vehicles[$i]['id']]
    );
}

require __DIR__ . '/../includes/header.php';
?>

<section class="container section">
    <div class="detail-grid company-header">
        <div class="stacked-panel">
            <span class="<?= e(badge_class($company['status'])) ?>"><?= e(ucfirst($company['status'])) ?></span>
            <h1><?= e($company['name']) ?></h1>
            <p class="lead"><?= e($company['address']) ?></p>
            <p><?= nl2br(e($company['description'] ?: 'This company has not added a profile description yet.')) ?></p>
        </div>
        <aside class="stacked-panel">
            <h2>Contact</h2>
            <p>Email: <?= e($company['contact_email'] ?: 'Not listed') ?></p>
            <p>Phone: <?= e($company['contact_phone'] ?: 'Not listed') ?></p>
            <p>Vehicles listed: <?= e((string) count($vehicles)) ?></p>
        </aside>
    </div>
</section>

<section class="container section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Fleet</span>
            <h2>Vehicles from <?= e($company['name']) ?></h2>
        </div>
    </div>
    <div class="card-grid">
        <?php foreach ($vehicles as $vehicle): ?>
            <article class="listing-card">
                <?php if (!empty($vehicle['image_name'])): ?>
                    <img src="<?= e(upload_url(VEHICLE_UPLOAD_DIR, $vehicle['image_name'])) ?>" alt="<?= e($vehicle['name']) ?>" class="listing-image">
                <?php else: ?>
                    <div class="listing-image placeholder">No image uploaded</div>
                <?php endif; ?>
                <div class="listing-content">
                    <div class="card-topline">
                        <span class="<?= e(badge_class($vehicle['status'])) ?>"><?= e(ucfirst($vehicle['status'])) ?></span>
                        <span class="muted"><?= e(ucfirst($vehicle['type'])) ?></span>
                    </div>
                    <h3><?= e($vehicle['name']) ?></h3>
                    <p><?= e($vehicle['location']) ?></p>
                    <strong><?= e(format_money((float) $vehicle['price_per_day'])) ?>/day</strong>
                    <div class="card-actions">
                        <a class="button button-primary" href="<?= e(url('vehicle.php?id=' . $vehicle['id'])) ?>">View vehicle</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$vehicles): ?>
            <div class="empty-state">
                <h3>No vehicles listed yet</h3>
                <p>This company has not published any fleet listings.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
