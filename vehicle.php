<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$vehicle = db_one(
    'SELECT v.*, u.company_name, u.phone AS company_phone, c.name AS category_name, t.name AS type_name
     FROM vehicles v JOIN users u ON u.id = v.company_id
     JOIN vehicle_categories c ON c.id = v.category_id
     JOIN vehicle_types t ON t.id = v.type_id
     WHERE v.id = ?',
    [$id]
);

if (!$vehicle) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="box"><h1>Vehicle not found</h1></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $vehicle['name'];
$maintenance = db_all('SELECT * FROM maintenance WHERE vehicle_id = ? ORDER BY start_date DESC', [$id]);
$reviewStats = vehicle_review_stats($id);
$reviews = db_all(
    'SELECT r.*, u.name AS user_name
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     WHERE r.vehicle_id = ? AND r.status = "published"
     ORDER BY r.id DESC
     LIMIT 6',
    [$id]
);
$me = current_user();
require __DIR__ . '/includes/header.php';
?>

<section class="detail">
    <div>
        <?php if ($vehicle['image']): ?>
            <img class="detail-img" src="<?= e(vehicle_image_src($vehicle['image'])) ?>" alt="<?= e($vehicle['name']) ?>">
        <?php else: ?>
            <div class="image-place big">No image uploaded</div>
        <?php endif; ?>
    </div>
    <div class="box">
        <span class="<?= e(role_badge($vehicle['status'])) ?>"><?= e($vehicle['status']) ?></span>
        <h1><?= e($vehicle['name']) ?></h1>
        <p class="rating-line"><?= e(rating_text($reviewStats['average_rating'], $reviewStats['review_count'])) ?></p>
        <p><?= e($vehicle['category_name']) ?> - <?= e($vehicle['type_name']) ?> from <?= e($vehicle['company_name']) ?>, <?= e($vehicle['location']) ?></p>
        <h2><?= e(money($vehicle['self_drive_price'])) ?> self-drive / day</h2>
        <h2><?= e(money($vehicle['with_driver_price'])) ?> with driver / day</h2>
        <p><?= e($vehicle['description']) ?></p>
        <p>GPS: <?= e($vehicle['latitude'] ?: 'N/A') ?>, <?= e($vehicle['longitude'] ?: 'N/A') ?></p>
        <p>Company phone: <?= e($vehicle['company_phone']) ?></p>
    </div>
</section>

<section class="grid two">
    <div class="box">
        <h2>Book this vehicle</h2>
        <div class="availability-calendar" data-availability-calendar data-vehicle-id="<?= e($vehicle['id']) ?>">
            <div class="calendar-head">
                <div>
                    <h3>Availability calendar</h3>
                    <p class="muted">Booked and maintenance dates are marked unavailable using the live booked dates API.</p>
                </div>
                <div class="calendar-nav">
                    <button class="btn light small" type="button" data-calendar-prev>&larr; Previous</button>
                    <button class="btn light small" type="button" data-calendar-next>Next &rarr;</button>
                </div>
            </div>
            <div class="calendar-legend">
                <span><i class="legend-dot available"></i> Available</span>
                <span><i class="legend-dot booked"></i> Booked</span>
                <span><i class="legend-dot maintenance"></i> Maintenance / blocked</span>
                <span><i class="legend-dot selected"></i> Selected dates</span>
            </div>
            <p class="muted calendar-summary" data-calendar-summary>Loading availability calendar...</p>
            <div class="calendar-months" data-calendar-months></div>
        </div>
        <?php if (!$me): ?>
            <p>Please login as user to book this vehicle.</p>
            <a class="btn" href="login.php">Login</a>
        <?php elseif ($me['role_name'] !== 'user'): ?>
            <p>Only user accounts can book vehicles.</p>
        <?php else: ?>
            <form class="simple-form" action="actions/booking.php" method="post" enctype="multipart/form-data" data-booking-form data-vehicle-id="<?= e($vehicle['id']) ?>" data-self-rate="<?= e($vehicle['self_drive_price']) ?>" data-driver-rate="<?= e($vehicle['with_driver_price']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="vehicle_id" value="<?= e($vehicle['id']) ?>">
                <label>Start date</label>
                <input type="date" name="start_date" min="<?= e(date('Y-m-d')) ?>" required>
                <label>End date</label>
                <input type="date" name="end_date" min="<?= e(date('Y-m-d')) ?>" required>
                <label class="check-line">
                    <input type="checkbox" name="with_driver" value="1" data-driver-check>
                    Book with driver
                </label>
                <label>Pickup location</label>
                <input type="text" name="pickup_location" maxlength="150" placeholder="Pickup point">
                <label>Destination</label>
                <input type="text" name="destination" maxlength="150" placeholder="Drop-off or trip destination">
                <label>Payment method</label>
                <select name="payment_method" required>
                    <option value="cash">Cash due after approval</option>
                    <option value="stripe">Stripe Checkout</option>
                </select>
                <div data-document-box>
                    <label>Citizenship/passport file</label>
                    <input type="file" name="document_file" data-doc-file required>
                    <label>License file</label>
                    <input type="file" name="license_file" data-license-file required>
                </div>
                <p class="muted">Documents are required only for self-drive booking.</p>
                <p class="availability-status" data-availability-status>Choose dates to check availability.</p>
                <p class="muted" data-booking-total></p>
                <label class="check-line">
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    I confirm these booking details are correct.
                </label>
                <p class="form-error" data-booking-error></p>
                <button class="btn" type="submit">Send booking request</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>Maintenance blackout</h2>
        <?php if (!$maintenance): ?>
            <p>No maintenance dates added.</p>
        <?php endif; ?>
        <?php foreach ($maintenance as $m): ?>
            <p><b><?= e($m['start_date']) ?></b> to <b><?= e($m['end_date']) ?></b><br><?= e($m['reason']) ?></p>
        <?php endforeach; ?>
    </div>
</section>

<section class="box">
    <h2>Customer reviews</h2>
    <?php if (!$reviews): ?>
        <p>No customer reviews yet.</p>
    <?php endif; ?>
    <div class="review-grid">
        <?php foreach ($reviews as $review): ?>
            <article class="review-card">
                <div class="card-line">
                    <strong><?= e($review['user_name']) ?></strong>
                    <span class="rating-pill"><?= e($review['rating']) ?>/5</span>
                </div>
                <p><?= e($review['comment'] ?: 'No written comment.') ?></p>
                <small class="muted"><?= e($review['created_at']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
