<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Browse Vehicles';
$pageDescription = 'Search available rental vehicles by type, company, and location.';
$pageScripts = [asset_url('js/vehicles.js')];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$companyId = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

$companies = db_all("SELECT id, name FROM companies WHERE status = 'approved' ORDER BY name");
$companyMap = [];

foreach ($companies as $company) {
    $companyMap[$company['id']] = $company['name'];
}

$allVehicles = db_all('SELECT * FROM vehicles ORDER BY created_at DESC');
$vehicles = [];

foreach ($allVehicles as $vehicle) {
    if (!isset($companyMap[$vehicle['company_id']])) {
        continue;
    }

    $matches = true;
    $companyName = $companyMap[$vehicle['company_id']];
    $searchText = strtolower($search);

    if ($searchText !== '') {
        $fullText = strtolower($vehicle['name'] . ' ' . $companyName . ' ' . $vehicle['location'] . ' ' . $vehicle['type']);

        if (strpos($fullText, $searchText) === false) {
            $matches = false;
        }
    }

    if ($matches && $type !== '' && in_array($type, VEHICLE_TYPES, true) && $vehicle['type'] !== $type) {
        $matches = false;
    }

    if ($matches && $companyId > 0 && (int) $vehicle['company_id'] !== $companyId) {
        $matches = false;
    }

    if ($matches && $location !== '' && stripos($vehicle['location'], $location) === false) {
        $matches = false;
    }

    if (!$matches) {
        continue;
    }

    $vehicle['company_name'] = $companyName;
    $vehicle['image_name'] = db_value(
        'SELECT file_name FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1',
        [$vehicle['id']]
    );
    $vehicles[] = $vehicle;
}

usort($vehicles, function ($a, $b) {
    if ($a['status'] === $b['status']) {
        return strcmp($b['created_at'], $a['created_at']);
    }

    if ($a['status'] === 'available') {
        return -1;
    }

    if ($b['status'] === 'available') {
        return 1;
    }

    return strcmp($b['created_at'], $a['created_at']);
});

require __DIR__ . '/../includes/header.php';
?>

<section class="container section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Vehicle Directory</span>
            <h1>Browse the fleet</h1>
            <p class="lead">Filter by type, company, or location. Results also update while you type.</p>
        </div>
    </div>

    <p class="muted" data-vehicle-summary><?= count($vehicles) ?> vehicle(s) found.</p>
    <p class="muted" data-vehicle-loading hidden>Loading vehicles...</p>

    <form
        method="get"
        class="filter-bar"
        data-vehicle-search-form
        data-api-url="<?= e(url('api/fetch_vehicles.php')) ?>"
        data-vehicle-url="<?= e(url('vehicle.php')) ?>"
        data-company-url="<?= e(url('company.php')) ?>"
    >
        <label>
            <span>Search</span>
            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="Search by name, company, or location"
                autocomplete="off"
            >
        </label>
        <label>
            <span>Type</span>
            <select name="type">
                <option value="">All types</option>
                <?php foreach (vehicle_type_options() as $optionValue => $optionLabel): ?>
                    <option value="<?= e($optionValue) ?>" <?= $type === $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Company</span>
            <select name="company_id">
                <option value="">All companies</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= e((string) $company['id']) ?>" <?= $companyId === (int) $company['id'] ? 'selected' : '' ?>><?= e($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Location</span>
            <input type="text" name="location" value="<?= e($location) ?>" placeholder="Kathmandu, Pokhara, ...">
        </label>
        <button type="submit" class="button button-primary">Apply Filters</button>
    </form>

    <div class="card-grid" data-vehicle-results>
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
                    <h2><?= e($vehicle['name']) ?></h2>
                    <p><?= e($vehicle['company_name']) ?> | <?= e($vehicle['location']) ?></p>
                    <div class="price-row">
                        <strong><?= e(format_money((float) $vehicle['price_per_day'])) ?>/day</strong>
                        <span>Driver: <?= e(format_money((float) $vehicle['driver_price_per_day'])) ?>/day</span>
                    </div>
                    <div class="card-actions">
                        <a class="button button-primary" href="<?= e(url('vehicle.php?id=' . $vehicle['id'])) ?>">Open Listing</a>
                        <a class="button button-secondary" href="<?= e(url('company.php?id=' . $vehicle['company_id'])) ?>">Company</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$vehicles): ?>
            <div class="empty-state">
                <h3>No vehicles match your filters</h3>
                <p>Try another search word or remove one filter.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
