<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';
$siteReviews = site_review_stats();
$homeVehiclesError = '';

try {
    [$featuredVehicles] = filtered_vehicles([], 3);
    [$mostRentedVehicles] = filtered_vehicles(['sort' => 'most_rented'], 3);
} catch (PDOException $exception) {
    db_log_error($exception, 'index.php featured vehicles');
    $featuredVehicles = [];
    $mostRentedVehicles = [];
    $homeVehiclesError = 'Featured vehicles could not be loaded right now.';
}

$homeCategories = db_all('SELECT * FROM vehicle_categories ORDER BY name LIMIT 6');
require __DIR__ . '/includes/header.php';
?>

<section class="hero home-hero">
    <div class="hero-inner">
        <h1>Find Your Perfect Ride</h1>
        <p>Rent verified vehicles from trusted companies with easy booking, flexible options, and clear approval tracking.</p>

        <form class="hero-search" action="vehicles.php" method="get">
            <label>
                <span>Location</span>
                <input type="text" name="search" placeholder="Kathmandu, Pokhara...">
            </label>
            <label>
                <span>Start date</span>
                <input type="date" name="start_date">
            </label>
            <label>
                <span>Vehicle type</span>
                <select name="category_id">
                    <option value="">All vehicles</option>
                    <?php foreach ($homeCategories as $category): ?>
                        <option value="<?= e($category['id']) ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn hero-search-btn" type="submit">Search</button>
        </form>
    </div>
</section>

<section class="home-section">
    <div class="section-title-center">
        <h2>Browse by Category</h2>
        <p>Choose the rental type that fits your trip.</p>
    </div>
    <div class="category-grid">
        <?php foreach ($homeCategories as $category): ?>
            <a class="category-card" href="vehicles.php?category_id=<?= e($category['id']) ?>">
                <span class="category-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><path d="M5 15.5h1.1a2.3 2.3 0 0 0 4.5 0h3a2.3 2.3 0 0 0 4.5 0H19a1.7 1.7 0 0 0 1.7-1.7v-2.1c0-.7-.4-1.3-1-1.5l-2.4-.9-2.1-2.7A2.7 2.7 0 0 0 13.1 5H8.4a2.8 2.8 0 0 0-2.5 1.6L4 10.4a3.6 3.6 0 0 0-.4 1.6v1.8A1.5 1.5 0 0 0 5 15.5Z" fill="currentColor"/></svg>
                </span>
                <strong><?= e($category['name']) ?></strong>
                <span>View available <?= e(strtolower($category['name'])) ?> rentals</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-section">
    <div class="section-title-row">
        <div>
            <h2>Featured Vehicles</h2>
            <p>Popular choices from the latest available listings.</p>
        </div>
        <a class="btn light" href="vehicles.php">View all vehicles</a>
    </div>

    <?php if ($homeVehiclesError !== ''): ?>
        <div class="alert danger"><?= e($homeVehiclesError) ?></div>
    <?php endif; ?>

    <div class="grid cards home-featured-grid">
        <?php foreach ($featuredVehicles as $v): ?>
            <article class="vehicle-card">
                <div class="vehicle-media">
                    <?php if ($v['image']): ?>
                        <img src="<?= e(vehicle_image_src($v['image'])) ?>" alt="<?= e($v['name']) ?>">
                    <?php else: ?>
                        <div class="image-place">Vehicle image coming soon</div>
                    <?php endif; ?>
                    <span class="vehicle-chip"><?= e($v['category_name']) ?></span>
                </div>
                <div class="vehicle-card-body">
                    <div class="card-line">
                        <div>
                            <h3><?= e($v['name']) ?></h3>
                            <p class="muted"><?= e($v['company_name']) ?></p>
                        </div>
                        <?= rating_html($v['average_rating'], $v['review_count']) ?>
                    </div>
                    <p class="vehicle-meta"><?= e($v['location']) ?> | <?= e($v['type_name']) ?></p>
                    <div class="vehicle-card-footer">
                        <p><b><?= e(money($v['self_drive_price'])) ?></b><span>/day</span></p>
                        <a class="btn small light" href="vehicle.php?id=<?= e($v['id']) ?>">View details</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-section">
    <div class="section-title-row">
        <div>
            <h2>Most Rented Vehicles</h2>
            <p>Top vehicles by confirmed or completed rental count.</p>
        </div>
        <a class="btn light" href="vehicles.php?sort=most_rented">Explore popular rentals</a>
    </div>

    <div class="grid cards home-featured-grid">
        <?php foreach ($mostRentedVehicles as $v): ?>
            <article class="vehicle-card">
                <div class="vehicle-media">
                    <?php if ($v['image']): ?>
                        <img src="<?= e(vehicle_image_src($v['image'])) ?>" alt="<?= e($v['name']) ?>">
                    <?php else: ?>
                        <div class="image-place">Vehicle image coming soon</div>
                    <?php endif; ?>
                    <span class="vehicle-chip"><?= e((int) ($v['rental_count'] ?? 0)) ?> rental(s)</span>
                </div>
                <div class="vehicle-card-body">
                    <div class="card-line">
                        <div>
                            <h3><?= e($v['name']) ?></h3>
                            <p class="muted"><?= e($v['company_name']) ?> · <?= e($v['location']) ?></p>
                        </div>
                        <?= rating_html($v['average_rating'], $v['review_count']) ?>
                    </div>
                    <div class="vehicle-card-footer">
                        <p><b><?= e(money($v['self_drive_price'])) ?></b><span>/day self-drive</span></p>
                        <a class="btn small light" href="vehicle.php?id=<?= e($v['id']) ?>">View details</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-section">
    <div class="grid three role-grid">
        <div class="card">
            <span class="card-kicker">Customer</span>
            <h3>Book confidently</h3>
            <p>Browse vehicles, verify availability, upload required documents, and track every rental request.</p>
        </div>
        <div class="card">
            <span class="card-kicker">Company</span>
            <h3>Manage your fleet</h3>
            <p>Add agents, update company details, review revenue, and monitor bookings from one dashboard.</p>
        </div>
        <div class="card">
            <span class="card-kicker">Agent</span>
            <h3>Control operations</h3>
            <p>Add vehicles, set maintenance blackout dates, and approve or reject customer requests.</p>
        </div>
    </div>
</section>

<section class="box rating-overview home-section">
    <div>
        <h2>Website rating</h2>
        <p class="muted">Average score from logged-in customer feedback.</p>
    </div>
    <strong><?= e($siteReviews['review_count'] ? number_format($siteReviews['average_rating'], 1) . '/5' : 'No reviews yet') ?></strong>
    <span><?= e($siteReviews['review_count']) ?> review(s)</span>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
