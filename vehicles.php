<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Vehicles';

$search = trim($_GET['search'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);
$typeId = (int) ($_GET['type_id'] ?? 0);
$minPrice = (float) ($_GET['min_price'] ?? 0);
$maxPrice = (float) ($_GET['max_price'] ?? 0);
$sort = trim((string) ($_GET['sort'] ?? 'latest'));
$vehiclesError = '';

try {
    [$vehicles] = filtered_vehicles($_GET, 100);
} catch (PDOException $exception) {
    db_log_error($exception, 'vehicles.php vehicle listing');
    $vehicles = [];
    $vehiclesError = 'Vehicles could not be loaded right now. Please try again after a moment.';
}

$categories = db_all('SELECT * FROM vehicle_categories ORDER BY name');
$types = db_all('SELECT * FROM vehicle_types ORDER BY category_id, name');

require __DIR__ . '/includes/header.php';
?>

<section class="section-head listing-head">
    <div>
        <h1>Available Vehicles</h1>
        <p>Search by vehicle, company, location, category, type, date and budget.</p>
    </div>
    <strong data-result-count><?= count($vehicles) ?> vehicle(s) found</strong>
</section>

<form class="search-bar" method="get" data-search-form>
    <label>
        <span>Search</span>
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Name, company, location" autocomplete="off">
    </label>
    <label>
        <span>Category</span>
        <select name="category_id" data-category-select>
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['id']) ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>Type</span>
        <select name="type_id" data-type-select>
            <option value="">All types</option>
            <?php foreach ($types as $type): ?>
                <option value="<?= e($type['id']) ?>" data-category="<?= e($type['category_id']) ?>" <?= $typeId === (int) $type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>Start date</span>
        <input type="date" name="start_date" value="<?= e($startDate) ?>">
    </label>
    <label>
        <span>End date</span>
        <input type="date" name="end_date" value="<?= e($endDate) ?>">
    </label>
    <label>
        <span>Min price</span>
        <input type="number" min="0" step="100" name="min_price" value="<?= $minPrice > 0 ? e($minPrice) : '' ?>" placeholder="Rs.">
    </label>
    <label>
        <span>Max price</span>
        <input type="number" min="0" step="100" name="max_price" value="<?= $maxPrice > 0 ? e($maxPrice) : '' ?>" placeholder="Rs.">
    </label>
    <label>
        <span>Sort</span>
        <select name="sort">
            <option value="latest" <?= $sort !== 'most_rented' ? 'selected' : '' ?>>Latest</option>
            <option value="most_rented" <?= $sort === 'most_rented' ? 'selected' : '' ?>>Most rented</option>
        </select>
    </label>
    <button class="btn" type="submit">Search</button>
</form>

<?php if ($vehiclesError !== ''): ?>
    <div class="alert danger"><?= e($vehiclesError) ?></div>
<?php endif; ?>

<section class="grid cards" data-vehicle-results>
    <?php foreach ($vehicles as $v): ?>
        <article class="vehicle-card">
            <div class="vehicle-media">
                <?php if ($v['image']): ?>
                    <img src="<?= e(vehicle_image_src($v['image'])) ?>" alt="<?= e($v['name']) ?>">
                <?php else: ?>
                    <div class="image-place">Vehicle image coming soon</div>
                <?php endif; ?>
                <span class="vehicle-chip"><?= e($v['category_name']) ?></span>
                <span class="vehicle-status-chip <?= e(role_badge($v['availability'] ?? $v['status'])) ?>"><?= e($v['availability'] ?? $v['status']) ?></span>
            </div>
            <div class="vehicle-card-body">
                <div class="card-line vehicle-title-row">
                    <div>
                        <h3><?= e($v['name']) ?></h3>
                        <p class="muted"><?= e($v['company_name']) ?></p>
                    </div>
                    <?= rating_html($v['average_rating'], $v['review_count']) ?>
                </div>
                <div class="vehicle-specs">
                    <span><?= e($v['seating_capacity'] ?: 'N/A') ?> seats</span>
                    <span><?= e($v['type_name']) ?></span>
                    <span><?= e($v['location']) ?></span>
                    <span><?= e($v['availability'] ?? $v['status']) ?></span>
                    <span><?= e((int) ($v['rental_count'] ?? 0)) ?> rental(s)</span>
                </div>
                <div class="vehicle-card-footer">
                    <p><b><?= e(money($v['self_drive_price'])) ?></b><span>/day self-drive</span></p>
                    <p><b><?= e(money($v['with_driver_price'])) ?></b><span>/day with driver</span></p>
                    <a class="btn small light" href="vehicle.php?id=<?= e($v['id']) ?>">View details</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
