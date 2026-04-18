<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$vehicleId = (int) ($_GET['id'] ?? 0);
$vehicle = db_one('SELECT * FROM vehicles WHERE id = ?', [$vehicleId]);
$company = null;

if ($vehicle) {
    $company = db_one('SELECT * FROM companies WHERE id = ?', [$vehicle['company_id']]);
}

if (!$vehicle || !$company || $company['status'] !== 'approved') {
    http_response_code(404);
    $pageTitle = 'Vehicle not found';
    require __DIR__ . '/../includes/header.php';
    ?>
    <section class="container section">
        <div class="empty-state">
            <h1>Vehicle not found</h1>
            <p>This listing does not exist or is not publicly available.</p>
            <a class="button button-primary" href="<?= e(url('vehicles.php')) ?>">Back to vehicles</a>
        </div>
    </section>
    <?php
    require __DIR__ . '/../includes/footer.php';
    return;
}

$vehicle['company_name'] = $company['name'];
$vehicle['company_profile_id'] = $company['id'];
$vehicle['company_description'] = $company['description'];
$vehicle['contact_phone'] = $company['contact_phone'];
$vehicle['contact_email'] = $company['contact_email'];
$vehicle['company_status'] = $company['status'];

$pageTitle = $vehicle['name'];
$pageDescription = 'View rental pricing, vehicle details, and submit a booking request.';

$viewer = current_user();
$images = db_all('SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC', [$vehicleId]);
$blocks = db_all(
    'SELECT * FROM availability_blocks
     WHERE vehicle_id = ? AND end_datetime >= NOW()
     ORDER BY start_datetime ASC
     LIMIT 5',
    [$vehicleId]
);
$locationOptions = location_options();
$estimatedPreview = format_money((float) $vehicle['price_per_day']);

require __DIR__ . '/../includes/header.php';
?>

<section class="container section">
    <div class="detail-grid">
        <div>
            <?php if ($images): ?>
                <div class="gallery-grid">
                    <?php foreach ($images as $image): ?>
                        <img src="<?= e(upload_url(VEHICLE_UPLOAD_DIR, $image['file_name'])) ?>" alt="<?= e($vehicle['name']) ?>" class="gallery-image">
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="listing-image placeholder detail-placeholder">No vehicle photos uploaded yet</div>
            <?php endif; ?>
        </div>

        <aside class="detail-sidebar">
            <span class="<?= e(badge_class($vehicle['status'])) ?>"><?= e(ucfirst($vehicle['status'])) ?></span>
            <h1><?= e($vehicle['name']) ?></h1>
            <p class="lead"><?= e(ucfirst($vehicle['type'])) ?> Ã¢â‚¬Â¢ <?= e($vehicle['location']) ?></p>
            <div class="price-stack">
                <strong><?= e(format_money((float) $vehicle['price_per_day'])) ?>/day</strong>
                <span>Driver add-on: <?= e(format_money((float) $vehicle['driver_price_per_day'])) ?>/day</span>
            </div>
            <ul class="detail-list">
                <li>Seats: <?= e((string) $vehicle['seating_capacity']) ?></li>
                <li>Transmission: <?= e($vehicle['transmission'] ?: 'Not specified') ?></li>
                <li>Fuel: <?= e($vehicle['fuel_type'] ?: 'Not specified') ?></li>
                <li>Company: <a href="<?= e(url('company.php?id=' . $vehicle['company_profile_id'])) ?>"><?= e($vehicle['company_name']) ?></a></li>
            </ul>
            <?php if ($vehicle['description']): ?>
                <p><?= nl2br(e($vehicle['description'])) ?></p>
            <?php endif; ?>
        </aside>
    </div>
</section>

<section class="container section">
    <div class="split-grid">
        <article class="stacked-panel">
            <span class="eyebrow">Booking</span>
            <h2>Request this vehicle</h2>
            <p>All bookings are charged by day, even if you select exact times. Confirmation happens after manual review by the company or agent.</p>

            <?php if (!$viewer): ?>
                <div class="empty-state">
                    <h3>Login required</h3>
                    <p>You need a user account to submit a booking request.</p>
                    <a class="button button-primary" href="<?= e(url('login.php')) ?>">Login</a>
                </div>
            <?php elseif ($viewer['role'] !== 'user'): ?>
                <div class="empty-state">
                    <h3>User accounts can book vehicles</h3>
                    <p>You are signed in as <?= e($viewer['role']) ?>. Switch to a user account to submit a renter booking.</p>
                </div>
            <?php else: ?>
                <form action="<?= e(url('actions/booking_create.php')) ?>" method="post" enctype="multipart/form-data" class="form-grid booking-form" data-booking-form data-base-price="<?= e((string) $vehicle['price_per_day']) ?>" data-driver-price="<?= e((string) $vehicle['driver_price_per_day']) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="vehicle_id" value="<?= e((string) $vehicle['id']) ?>">

                    <label>
                        <span>Start date & time</span>
                        <input type="datetime-local" name="start_datetime" required>
                    </label>
                    <label>
                        <span>End date & time</span>
                        <input type="datetime-local" name="end_datetime" required>
                    </label>
                    <label>
                        <span>Pickup location</span>
                        <input list="location-list" name="pickup_location" placeholder="Select or type a pickup point" required>
                    </label>
                    <label>
                        <span>Destination</span>
                        <input type="text" name="destination" placeholder="Drop-off or trip destination" required>
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="with_driver" value="1" data-driver-toggle>
                        <span>Need a driver for this trip</span>
                    </label>
                    <label>
                        <span>Identity document type</span>
                        <select name="identity_type">
                            <option value="passport">Passport</option>
                            <option value="citizenship">Citizenship</option>
                        </select>
                    </label>
                    <label>
                        <span>Upload license</span>
                        <input type="file" name="license_document" accept=".jpg,.jpeg,.png,.pdf" required>
                    </label>
                    <label>
                        <span>Upload passport/citizenship</span>
                        <input type="file" name="identity_document" accept=".jpg,.jpeg,.png,.pdf" required>
                    </label>

                    <div class="full-width booking-summary">
                        <strong>Estimated from <?= e($estimatedPreview) ?>/day</strong>
                        <span data-price-preview>Select dates to preview the total.</span>
                    </div>

                    <div class="full-width terms-row">
                        <label class="checkbox-row">
                            <input type="checkbox" name="terms_accepted" value="1" required>
                            <span>I accept the rental terms and conditions.</span>
                        </label>
                        <button type="button" class="button button-secondary" data-open-modal="terms-modal">Read Terms</button>
                    </div>

                    <div class="full-width">
                        <button type="submit" class="button button-primary">Submit Booking Request</button>
                    </div>
                </form>
            <?php endif; ?>
        </article>

        <article class="stacked-panel">
            <span class="eyebrow">Availability Notes</span>
            <h2>Upcoming blackout periods</h2>
            <?php if ($blocks): ?>
                <div class="table-stack">
                    <?php foreach ($blocks as $block): ?>
                        <div class="mini-row">
                            <strong><?= e(format_datetime($block['start_datetime'])) ?></strong>
                            <span>to <?= e(format_datetime($block['end_datetime'])) ?></span>
                            <p><?= e($block['reason'] ?: 'Maintenance or manual blackout') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No upcoming blackout periods are currently recorded.</p>
            <?php endif; ?>

            <div class="stacked-panel soft">
                <h3>Company contact</h3>
                <p><?= e($vehicle['company_name']) ?></p>
                <p><?= e($vehicle['contact_phone'] ?: 'Phone not listed') ?></p>
                <p><?= e($vehicle['contact_email'] ?: 'Email not listed') ?></p>
            </div>
        </article>
    </div>
</section>

<datalist id="location-list">
    <?php foreach ($locationOptions as $option): ?>
        <option value="<?= e($option) ?>"></option>
    <?php endforeach; ?>
</datalist>

<div class="modal" id="terms-modal" hidden>
    <div class="modal-card">
        <button type="button" class="modal-close" data-close-modal>&times;</button>
        <h2>Rental Terms & Conditions</h2>
        <ul class="detail-list">
            <li>Bookings are requests until the company or agent confirms them.</li>
            <li>Pricing is charged by day in this MVP, even when times are provided.</li>
            <li>Valid license and identity documents must be uploaded before approval.</li>
            <li>Cash payment is collected offline after confirmation.</li>
            <li>Users can cancel only pending bookings from their dashboard.</li>
        </ul>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
