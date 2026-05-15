<?php
require_once __DIR__ . '/includes/functions.php';

require_role('user');

$me = current_user();
$bookingId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Booking details';

$booking = null;
$review = null;

if ($bookingId > 0) {
    $booking = db_one(
        'SELECT b.*, v.name AS vehicle_name, v.location AS vehicle_location, v.image,
                vc.name AS category_name, vt.name AS type_name,
                COALESCE(company.company_name, company.name, "Company") AS company_display_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users company ON company.id = b.company_id
         LEFT JOIN vehicle_categories vc ON vc.id = v.category_id
         LEFT JOIN vehicle_types vt ON vt.id = v.type_id
         WHERE b.id = ? AND b.user_id = ?
         LIMIT 1',
        [$bookingId, (int) $me['id']]
    );

    if ($booking) {
        $review = db_one(
            'SELECT * FROM reviews WHERE booking_id = ? AND user_id = ? LIMIT 1',
            [$bookingId, (int) $me['id']]
        );
    }
}

include __DIR__ . '/includes/header.php';
?>

<?php if (!$booking): ?>
    <section class="box dashboard-panel">
        <div class="empty-state">
            <h3>Booking not found</h3>
            <p>This booking does not exist or does not belong to your account.</p>
            <a class="btn" href="dashboard.php?section=bookings">Back to bookings</a>
        </div>
    </section>
<?php else: ?>
    <?php
    $payment = payment_for_booking((int) $booking['id']);
    $paymentStatus = $booking['payment_status'] ?? ($payment['status'] ?? 'cash_due');
    $canReview = !$review && user_can_review_booking($booking);
    ?>

    <section class="booking-detail-head">
        <a class="btn light small" href="dashboard.php?section=bookings">Back to bookings</a>
        <div>
            <h1>Booking #<?= e($booking['id']) ?></h1>
            <p><?= e($booking['vehicle_name']) ?> with <?= e($booking['company_display_name']) ?></p>
        </div>
    </section>

    <section class="box dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h2>Rental summary</h2>
                <p class="muted">Status, trip date, and payment details for this booking.</p>
            </div>
            <span class="<?= e(role_badge($booking['status'])) ?>"><?= e($booking['status']) ?></span>
        </div>

        <div class="booking-detail-grid">
            <span><b>Vehicle</b><?= e($booking['vehicle_name']) ?></span>
            <span><b>Vehicle type</b><?= e(trim(($booking['category_name'] ?? '') . ' ' . ($booking['type_name'] ?? '')) ?: 'Not set') ?></span>
            <span><b>Company</b><?= e($booking['company_display_name']) ?></span>
            <span><b>Dates</b><?= e($booking['start_date']) ?> to <?= e($booking['end_date']) ?></span>
            <span><b>Driver</b><?= $booking['with_driver'] ? 'With driver' : 'Self drive' ?></span>
            <span><b>Total price</b><?= e(money($booking['total_price'])) ?></span>
            <span><b>Payment</b><span class="<?= e(payment_badge($paymentStatus)) ?>"><?= e($paymentStatus) ?></span></span>
            <span><b>Pickup</b><?= e($booking['pickup_location'] ?: 'Not set') ?></span>
            <span><b>Destination</b><?= e($booking['destination'] ?: 'Not set') ?></span>
        </div>
    </section>

    <section class="box dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h2>Post-rental review</h2>
                <p class="muted">Review form is available only after a completed paid rental.</p>
            </div>
        </div>

        <?php if ($review): ?>
            <div class="review-state-card">
                <div class="card-line">
                    <strong>Review already submitted</strong>
                    <span class="rating-pill"><?= e($review['rating']) ?>/5</span>
                </div>
                <form class="simple-form" aria-label="Submitted review">
                    <label>Rating</label>
                    <div class="submitted-stars" aria-label="<?= e($review['rating']) ?> out of 5">
                        <?= str_repeat('&#9733;', (int) $review['rating']) ?>
                    </div>
                    <label>Comment</label>
                    <textarea disabled><?= e($review['comment'] ?: 'No written comment.') ?></textarea>
                    <button class="btn small" type="button" disabled>Review already submitted</button>
                </form>
            </div>
        <?php elseif ($canReview): ?>
            <form class="simple-form review-form" action="actions/review.php" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">

                <label>Rating</label>
                <div class="star-rating" role="radiogroup" aria-label="Choose rating from 1 to 5 stars">
                    <?php for ($stars = 5; $stars >= 1; $stars--): ?>
                        <label>
                            <input type="radio" name="rating" value="<?= e($stars) ?>" required>
                            <span aria-hidden="true"><?= str_repeat('&#9733;', $stars) ?></span>
                            <small><?= e($stars) ?>/5</small>
                        </label>
                    <?php endfor; ?>
                </div>

                <label for="review-comment">Comment</label>
                <textarea id="review-comment" name="comment" placeholder="Share your rental experience"></textarea>

                <button class="btn" type="submit">Submit review</button>
            </form>
        <?php elseif ($booking['status'] !== 'completed'): ?>
            <div class="empty-state">
                <h3>Review opens after completion</h3>
                <p>This booking is currently <?= e($booking['status']) ?>. The review form will show after the rental is completed.</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>Review not available yet</h3>
                <p>The booking is completed, but it must also be paid and past the end date before review submission.</p>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
