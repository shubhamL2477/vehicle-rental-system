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
$bookingVehicleOptions = $me && $me['role_name'] === 'user' ? available_vehicle_options(0) : [];
require __DIR__ . '/includes/header.php';
?>

<a class="back-link" href="vehicles.php">Back to Vehicles</a>

<section class="detail detail-hero">
    <div class="detail-gallery">
        <?php if ($vehicle['image']): ?>
            <img class="detail-img" src="<?= e(vehicle_image_src($vehicle['image'])) ?>" alt="<?= e($vehicle['name']) ?>">
        <?php else: ?>
            <div class="image-place big">Vehicle image coming soon</div>
        <?php endif; ?>
        <span class="gallery-control gallery-control-left" aria-hidden="true">&#8249;</span>
        <span class="gallery-control gallery-control-right" aria-hidden="true">&#8250;</span>
    </div>
    <div class="box detail-summary">
        <span class="<?= e(role_badge($vehicle['status'])) ?>"><?= e($vehicle['status']) ?></span>
        <h1><?= e($vehicle['name']) ?></h1>
        <?= rating_html($reviewStats['average_rating'], $reviewStats['review_count']) ?>
        <p class="detail-meta"><?= e($vehicle['category_name']) ?> | <?= e($vehicle['type_name']) ?> | <?= e($vehicle['location']) ?></p>
        <p class="muted">Listed by <?= e($vehicle['company_name']) ?></p>
        <div class="price-stack">
            <span><b><?= e(money($vehicle['self_drive_price'])) ?></b> self-drive / day</span>
            <span><b><?= e(money($vehicle['with_driver_price'])) ?></b> with driver / day</span>
        </div>
        <p><?= e($vehicle['description']) ?></p>
        <div class="detail-facts">
            <span
                data-gps-tracker
                data-lat="<?= e($vehicle['latitude'] ?: '') ?>"
                data-lng="<?= e($vehicle['longitude'] ?: '') ?>"
            ><b>GPS</b><?= e($vehicle['latitude'] ?: 'N/A') ?>, <?= e($vehicle['longitude'] ?: 'N/A') ?></span>
            <span><b>Company phone</b><?= e($vehicle['company_phone']) ?></span>
        </div>
    </div>
</section>

<section class="grid two booking-detail-grid">
    <div class="box">
        <h2>Book this vehicle</h2>
        <?php if (!$me): ?>
            <p>Please login as user to book this vehicle.</p>
            <a class="btn" href="login.php">Login</a>
        <?php elseif ($me['role_name'] !== 'user'): ?>
            <p>Only user accounts can book vehicles.</p>
        <?php else: ?>
            <form class="simple-form" action="actions/booking.php" method="post" enctype="multipart/form-data" data-booking-form data-vehicle-id="<?= e($vehicle['id']) ?>" data-self-rate="<?= e($vehicle['self_drive_price']) ?>" data-driver-rate="<?= e($vehicle['with_driver_price']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <label>Vehicle</label>
                <select name="vehicle_id" data-booking-vehicle-select required>
                    <?php foreach ($bookingVehicleOptions as $option): ?>
                        <option
                            value="<?= e($option['id']) ?>"
                            data-self-rate="<?= e($option['self_drive_price']) ?>"
                            data-driver-rate="<?= e($option['with_driver_price']) ?>"
                            <?= (int) $option['id'] === (int) $vehicle['id'] ? 'selected' : '' ?>
                        >
                            <?= e($option['name'] . ' | ' . $option['location'] . ' | ' . money($option['self_drive_price']) . ' self-drive') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
        <h2>Availability calendar</h2>
        <div class="availability-calendar" data-availability-calendar>
            <div class="calendar-head">
                <button class="btn light tiny" type="button" data-calendar-prev>Prev</button>
                <strong data-calendar-title>Loading</strong>
                <button class="btn light tiny" type="button" data-calendar-next>Next</button>
            </div>
            <div class="calendar-weekdays">
                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
            </div>
            <div class="calendar-grid" data-calendar-grid></div>
            <div class="calendar-legend">
                <span><i class="calendar-free"></i>Available</span>
                <span><i class="calendar-blocked"></i>Unavailable</span>
            </div>
        </div>
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
