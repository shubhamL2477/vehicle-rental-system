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

<section class="section-head">
    <h1>Available vehicles</h1>
    <p>Search by vehicle, company, location, category, type, date and budget.</p>
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
    <button class="btn" type="submit">Search</button>
</form>

<p class="muted" data-result-count><?= count($vehicles) ?> vehicle(s) found</p>
<?php if ($vehiclesError !== ''): ?>
    <div class="alert danger"><?= e($vehiclesError) ?></div>
<?php endif; ?>

<section class="grid cards" data-vehicle-results>
    <?php foreach ($vehicles as $v): ?>
        <article class="vehicle-card">
            <?php if ($v['image']): ?>
                <img src="<?= e(vehicle_image_src($v['image'])) ?>" alt="<?= e($v['name']) ?>">
            <?php else: ?>
                <div class="image-place">No image</div>
            <?php endif; ?>
            <h3><?= e($v['name']) ?></h3>
            <p class="rating-line"><?= e(rating_text($v['average_rating'], $v['review_count'])) ?></p>
            <p><?= e($v['company_name']) ?> | <?= e($v['location']) ?> | <?= e($v['category_name']) ?> - <?= e($v['type_name']) ?></p>
            <p><b><?= e(money($v['self_drive_price'])) ?></b> self-drive</p>
            <p><b><?= e(money($v['with_driver_price'])) ?></b> with driver</p>
            <a class="btn small" href="vehicle.php?id=<?= e($v['id']) ?>">View</a>
        </article>
    <?php endforeach; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
