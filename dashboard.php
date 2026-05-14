<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/backend/models/NotificationService.php';
require_login();

$me = current_user();
$pageTitle = 'Dashboard';
$editId = (int) ($_GET['edit'] ?? 0);
$section = $_GET['section'] ?? '';
$editVehicle = null;
$isPlatformAdmin = is_platform_admin_role($me['role_name']);
$showVehicleForm = false;

$roleDashboardSections = [
    'user' => ['bookings', 'reviews', 'notifications'],
    'company' => ['maintenance', 'revenue', 'bookings'],
    'agent' => ['vehicles', 'bookings', 'maintenance'],
];

if ($isPlatformAdmin) {
    $roleDashboardSections[$me['role_name']] = ['analytics', 'ratings'];
}

$allowedSections = $roleDashboardSections[$me['role_name']] ?? ['overview'];
$defaultSection = $allowedSections[0];

if ($section === '' || !in_array($section, $allowedSections, true)) {
    $section = $defaultSection;
}

if ($me['role_name'] === 'agent' && $editId > 0) {
    $editVehicle = db_one('SELECT * FROM vehicles WHERE id = ? AND company_id = ?', [$editId, $me['company_id']]);
    if ($editVehicle) {
        $showVehicleForm = true;
        $section = 'vehicles';
    }
}

$categories = db_all('SELECT * FROM vehicle_categories ORDER BY name');
$types = db_all('SELECT * FROM vehicle_types ORDER BY category_id, name');

require __DIR__ . '/includes/header.php';
?>

<section class="section-head">
    <h1><?= e(ucwords(str_replace('_', ' ', $me['role_name']))) ?> dashboard</h1>
    <p>Welcome back, <?= e($me['name']) ?>.</p>
</section>

<?php if ($me['role_name'] === 'user'): ?>
    <?php
    $bookings = db_all(
        'SELECT b.*, v.name AS vehicle_name, u.company_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.company_id
         WHERE b.user_id = ?
         ORDER BY b.id DESC',
        [$me['id']]
    );
    $totalBookings = count($bookings);
    $pendingBookings = 0;
    $approvedBookings = 0;
    $cancelledBookings = 0;

    foreach ($bookings as $bookingCountRow) {
        if ($bookingCountRow['status'] === 'pending') {
            $pendingBookings++;
        }
        if ($bookingCountRow['status'] === 'approved') {
            $approvedBookings++;
        }
        if ($bookingCountRow['status'] === 'cancelled') {
            $cancelledBookings++;
        }
    }

    $recentBookings = array_slice($bookings, 0, 3);
    $reviewableBookings = db_all(
        'SELECT b.*, v.name AS vehicle_name, u.company_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.company_id
         LEFT JOIN reviews r ON r.booking_id = b.id
         WHERE b.user_id = ?
           AND b.status IN ("approved", "confirmed", "completed")
           AND b.payment_status = "paid"
           AND b.end_date <= CURDATE()
           AND r.id IS NULL
         ORDER BY b.end_date DESC',
        [$me['id']]
    );
    $myReviews = db_all(
        'SELECT r.*, v.name AS vehicle_name
         FROM reviews r
         JOIN vehicles v ON v.id = r.vehicle_id
         WHERE r.user_id = ?
         ORDER BY r.id DESC',
        [$me['id']]
    );
    $notifications = NotificationService::latestForUser((int) $me['id'], 12);
    $rentalReminders = rental_reminders_for_user((int) $me['id']);
    $canRateSite = user_can_rate_site((int) $me['id']);
    ?>
    <nav class="dashboard-tabs">
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">My bookings</a>
        <a class="<?= $section === 'reviews' ? 'active' : '' ?>" href="dashboard.php?section=reviews">Reviews</a>
        <a class="<?= $section === 'notifications' ? 'active' : '' ?>" href="dashboard.php?section=notifications">Notifications</a>
    </nav>

    <section class="metric-grid">
        <article class="metric-card">
            <strong><?= e($totalBookings) ?></strong>
            <span>Total bookings</span>
        </article>
        <article class="metric-card">
            <strong><?= e($pendingBookings) ?></strong>
            <span>Waiting approval</span>
        </article>
        <article class="metric-card">
            <strong><?= e($approvedBookings) ?></strong>
            <span>Approved trips</span>
        </article>
        <article class="metric-card">
            <strong><?= e($cancelledBookings) ?></strong>
            <span>Cancelled</span>
        </article>
    </section>

    <?php if ($section === 'bookings'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>My bookings</h2>
                    <p class="muted">Extend, change vehicle, or cancel active bookings from one place.</p>
                </div>
                <a class="btn" href="vehicles.php">New booking</a>
            </div>

            <div class="booking-list">
                <?php foreach ($bookings as $b): ?>
                    <?php $vehicleOptions = available_vehicle_options((int) $b['vehicle_id']); ?>
                    <?php $payment = payment_for_booking((int) $b['id']); ?>
                    <?php $bookingPaymentStatus = $b['payment_status'] ?? ($payment['status'] ?? 'cash_due'); ?>
                    <article class="booking-row-card">
                        <div class="booking-row-main">
                            <div>
                                <h3><?= e($b['vehicle_name']) ?></h3>
                                <p><?= e($b['company_name']) ?> · <?= $b['with_driver'] ? 'With driver' : 'Self drive' ?></p>
                            </div>
                            <span class="<?= e(role_badge($b['status'])) ?>"><?= e($b['status']) ?></span>
                        </div>

                        <div class="booking-row-details">
                            <span><b>Dates</b><?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></span>
                            <span><b>Total</b><?= e(money($b['total_price'])) ?></span>
                            <span><b>Payment</b><span class="<?= e(payment_badge($bookingPaymentStatus)) ?>"><?= e($bookingPaymentStatus) ?></span></span>
                            <span><b>Note</b><?= e($b['agent_note'] ?: 'No note') ?></span>
                        </div>

                        <?php if (in_array($b['status'], ['pending', 'approved'], true)): ?>
                            <div class="booking-actions">
                                <?php if (($b['payment_method'] ?? 'cash') === 'stripe' && $bookingPaymentStatus !== 'paid'): ?>
                                    <form action="actions/payment.php" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="stripe_retry">
                                        <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                        <button class="btn small" type="submit"><?= $bookingPaymentStatus === 'failed' ? 'Retry Stripe' : 'Continue Stripe' ?></button>
                                    </form>
                                <?php elseif (($b['payment_method'] ?? 'cash') === 'stripe'): ?>
                                    <a class="btn light small" href="payment-status.php?booking_id=<?= e($b['id']) ?>">Payment status</a>
                                <?php elseif ($b['status'] === 'approved' && (!$payment || $payment['status'] !== 'paid')): ?>
                                    <form action="actions/payment.php" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="start">
                                        <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                        <button class="btn small" type="submit">Pay with Khalti</button>
                                    </form>
                                <?php elseif ($payment): ?>
                                    <a class="btn light small" href="payment-status.php?payment_id=<?= e($payment['id']) ?>">Payment status</a>
                                <?php endif; ?>

                                <form action="actions/booking.php" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="extend">
                                    <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                    <label>
                                        <span>Extend until</span>
                                        <input type="date" name="end_date" value="<?= e(date('Y-m-d', strtotime($b['end_date'] . ' +1 day'))) ?>" min="<?= e(date('Y-m-d', strtotime($b['end_date'] . ' +1 day'))) ?>" required>
                                    </label>
                                    <button class="btn small" type="submit">Extend</button>
                                </form>

                                <form action="actions/booking.php" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="change_vehicle">
                                    <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                    <label>
                                        <span>Change vehicle</span>
                                        <select name="vehicle_id" required>
                                            <option value="">Select vehicle</option>
                                            <?php foreach ($vehicleOptions as $option): ?>
                                                <option value="<?= e($option['id']) ?>">
                                                    <?= e($option['name'] . ' · ' . $option['category_name'] . ' · ' . $option['location'] . ' · ' . money($option['self_drive_price'])) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <button class="btn small" type="submit">Change</button>
                                </form>

                                <form action="actions/booking.php" method="post" onsubmit="return confirm('Cancel this booking?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                    <button class="btn danger small" type="submit">Cancel</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (!$bookings): ?>
                <div class="empty-state">
                    <h3>No bookings yet</h3>
                    <p>Browse vehicles and submit your first rental request.</p>
                    <a class="btn" href="vehicles.php">Browse vehicles</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'reviews'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Reviews</h2>
                    <p class="muted">Reviews are allowed only after a rental is paid and the trip end date has passed.</p>
                </div>
            </div>

            <div class="review-grid">
                <?php foreach ($reviewableBookings as $b): ?>
                    <article class="review-card">
                        <h3><?= e($b['vehicle_name']) ?></h3>
                        <p><?= e($b['company_name']) ?> · <?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></p>
                        <form class="simple-form" action="actions/review.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                            <label>Rating</label>
                            <select name="rating" required>
                                <option value="">Select rating</option>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Good</option>
                                <option value="3">3 - Average</option>
                                <option value="2">2 - Poor</option>
                                <option value="1">1 - Bad</option>
                            </select>
                            <label>Comment</label>
                            <textarea name="comment" placeholder="Share your rental experience"></textarea>
                            <button class="btn small" type="submit">Submit review</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (!$reviewableBookings): ?>
                <div class="empty-state">
                    <h3>No rentals ready for review</h3>
                    <p>A booking becomes reviewable after payment is successful and the end date has passed.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="box dashboard-panel">
            <h2>My submitted reviews</h2>
            <div class="review-grid">
                <?php foreach ($myReviews as $review): ?>
                    <article class="review-card">
                        <div class="card-line">
                            <strong><?= e($review['vehicle_name']) ?></strong>
                            <span class="rating-pill"><?= e($review['rating']) ?>/5</span>
                        </div>
                        <p><?= e($review['comment'] ?: 'No written comment.') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$myReviews): ?>
                <p class="muted">No reviews submitted yet.</p>
            <?php endif; ?>
        </section>

        <section class="box dashboard-panel">
            <h2>Rate Hyrox Rental</h2>
            <?php if ($canRateSite): ?>
                <form class="simple-form" action="actions/review.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="site_rating">
                    <label>Website/service rating</label>
                    <select name="rating" required>
                        <option value="">Select rating</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Bad</option>
                    </select>
                    <label>Feedback</label>
                    <textarea name="feedback" maxlength="1000" placeholder="Tell us about the service"></textarea>
                    <button class="btn small" type="submit">Submit website rating</button>
                </form>
            <?php else: ?>
                <p class="muted">You have already rated the website. Thank you.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'notifications'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Notifications</h2>
                    <p class="muted">Recent booking, payment, and reminder updates.</p>
                </div>
                <form action="actions/notification.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mark_all_read">
                    <button class="btn light small" type="submit">Mark all read</button>
                </form>
            </div>
            <?php if ($rentalReminders): ?>
                <div class="notification-list">
                    <?php foreach ($rentalReminders as $reminder): ?>
                        <article class="notification-card reminder <?= e($reminder['reminder_type']) ?>">
                            <strong><?= e(ucwords(str_replace('_', ' ', $reminder['reminder_type']))) ?></strong>
                            <p><?= e($reminder['vehicle_name']) ?> · <?= e($reminder['start_date']) ?> to <?= e($reminder['end_date']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notice): ?>
                    <article class="notification-card <?= $notice['read_at'] ? 'read' : 'unread' ?>">
                        <div class="card-line">
                            <strong><?= e($notice['title']) ?></strong>
                            <span class="badge <?= $notice['read_at'] ? 'good' : 'wait' ?>"><?= $notice['read_at'] ? 'read' : 'new' ?></span>
                        </div>
                        <p><?= e($notice['message']) ?></p>
                        <small class="muted"><?= e($notice['created_at']) ?> · <?= e($notice['type']) ?></small>
                        <?php if (!$notice['read_at']): ?>
                            <form action="actions/notification.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="notification_id" value="<?= e($notice['id']) ?>">
                                <button class="btn light tiny" type="submit">Mark read</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$notifications): ?>
                <div class="empty-state"><h3>No notifications</h3><p>Booking updates will appear here.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php if ($isPlatformAdmin): ?>
    <?php
    $adminStats = db_one(
        'SELECT COUNT(*) AS total_bookings,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_bookings,
                SUM(CASE WHEN status IN ("approved", "confirmed", "completed") THEN 1 ELSE 0 END) AS active_bookings,
                SUM(CASE WHEN payment_status = "paid" THEN total_price ELSE 0 END) AS paid_revenue
         FROM bookings'
    );

    $ratingStats = site_review_stats();
    $siteRatings = db_all(
        'SELECT sr.*, u.name AS user_name, u.email AS user_email
         FROM site_reviews sr
         JOIN users u ON u.id = sr.user_id
         ORDER BY sr.created_at DESC
         LIMIT 20'
    );
    ?>

    <nav class="dashboard-tabs">
        <a class="<?= $section === 'analytics' ? 'active' : '' ?>" href="dashboard.php?section=analytics">Platform analytics</a>
        <a class="<?= $section === 'ratings' ? 'active' : '' ?>" href="dashboard.php?section=ratings">Website ratings</a>
    </nav>

    <section class="metric-grid">
        <article class="metric-card">
            <strong><?= e($adminStats['total_bookings'] ?? 0) ?></strong>
            <span>Total bookings</span>
        </article>
        <article class="metric-card">
            <strong><?= e($adminStats['pending_bookings'] ?? 0) ?></strong>
            <span>Pending bookings</span>
        </article>
        <article class="metric-card">
            <strong><?= e(money($adminStats['paid_revenue'] ?? 0)) ?></strong>
            <span>Paid platform revenue</span>
        </article>
        <article class="metric-card">
            <strong><?= e(rating_text($ratingStats['average_rating'], $ratingStats['review_count'])) ?></strong>
            <span>Website ratings</span>
        </article>
    </section>

    <?php if ($section === 'analytics'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Platform analytics</h2>
                    <p class="muted">Bookings, paid revenue, and public website rating health.</p>
                </div>
            </div>
            <div class="chart-bars">
                <div><span style="height: <?= e(min(100, max(8, (int) ($adminStats['pending_bookings'] ?? 0) * 12))) ?>%"></span><b>Pending</b></div>
                <div><span style="height: <?= e(min(100, max(8, (int) ($adminStats['active_bookings'] ?? 0) * 12))) ?>%"></span><b>Active</b></div>
                <div><span style="height: <?= e(min(100, max(8, (int) ($ratingStats['average_rating'] * 20)))) ?>%"></span><b>Rating</b></div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($section === 'ratings'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Website ratings</h2>
                    <p class="muted">Recent customer ratings and feedback for the platform experience.</p>
                </div>
                <strong><?= e(rating_text($ratingStats['average_rating'], $ratingStats['review_count'])) ?></strong>
            </div>
            <div class="review-grid">
                <?php foreach ($siteRatings as $siteRating): ?>
                    <article class="review-card">
                        <div class="card-line">
                            <strong><?= e($siteRating['user_name']) ?></strong>
                            <span class="rating-pill"><?= e($siteRating['rating']) ?>/5</span>
                        </div>
                        <p><?= e($siteRating['feedback'] ?: 'No written feedback.') ?></p>
                        <small class="muted"><?= e($siteRating['user_email']) ?> · <?= e($siteRating['created_at']) ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$siteRatings): ?>
                <p class="muted">No website/service ratings yet.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php if ($me['role_name'] === 'company'): ?>
    <?php
    $vehicles = db_all(
        'SELECT v.*, c.name AS category_name, t.name AS type_name
         FROM vehicles v
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         WHERE v.company_id = ?
         ORDER BY v.id DESC',
        [$me['id']]
    );
    $companyBookings = db_all(
        'SELECT b.*, v.name AS vehicle_name, u.name AS user_name, u.email AS user_email
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.user_id
         WHERE b.company_id = ?
         ORDER BY b.created_at DESC, b.id DESC',
        [$me['id']]
    );
    $companyMaintenanceRows = db_all(
        'SELECT m.*, v.name AS vehicle_name
         FROM maintenance m
         JOIN vehicles v ON v.id = m.vehicle_id
         WHERE v.company_id = ?
         ORDER BY m.start_date DESC, m.id DESC',
        [$me['id']]
    );
    $companyRevenueMonth = (int) ($_GET['month'] ?? date('n'));
    $companyRevenueYear = (int) ($_GET['year'] ?? date('Y'));
    $companyRevenueMonth = $companyRevenueMonth >= 1 && $companyRevenueMonth <= 12 ? $companyRevenueMonth : (int) date('n');
    $companyRevenueYear = $companyRevenueYear >= 2020 && $companyRevenueYear <= 2100 ? $companyRevenueYear : (int) date('Y');
    $companyRevenueStart = sprintf('%04d-%02d-01', $companyRevenueYear, $companyRevenueMonth);
    $companyRevenueEnd = date('Y-m-t', strtotime($companyRevenueStart));
    $companyRevenueRows = db_all(
        'SELECT v.name AS vehicle_name, COALESCE(SUM(b.total_price), 0) AS revenue_total, COUNT(*) AS paid_bookings
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.company_id = ?
           AND b.payment_status = "paid"
           AND b.start_date BETWEEN ? AND ?
         GROUP BY v.id, v.name
         ORDER BY revenue_total DESC',
        [$me['id'], $companyRevenueStart, $companyRevenueEnd]
    );
    $companyRevenueTotal = 0;
    foreach ($companyRevenueRows as $companyRevenueRow) {
        $companyRevenueTotal += (float) $companyRevenueRow['revenue_total'];
    }
    ?>

    <nav class="dashboard-tabs">
        <a class="<?= $section === 'maintenance' ? 'active' : '' ?>" href="dashboard.php?section=maintenance">Maintenance</a>
        <a class="<?= $section === 'revenue' ? 'active' : '' ?>" href="dashboard.php?section=revenue">Revenue</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">Bookings</a>
    </nav>

    <section class="metric-grid">
        <article class="metric-card"><strong><?= e(count($companyMaintenanceRows)) ?></strong><span>Maintenance records</span></article>
        <article class="metric-card"><strong><?= e(money($companyRevenueTotal)) ?></strong><span>Monthly paid revenue</span></article>
        <article class="metric-card"><strong><?= e(count($companyBookings)) ?></strong><span>Bookings</span></article>
        <article class="metric-card"><strong><?= e(count($vehicles)) ?></strong><span>Fleet vehicles</span></article>
    </section>

    <?php if ($section === 'maintenance'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Maintenance</h2>
                    <p class="muted">Maintenance blackouts across your company fleet.</p>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>Vehicle</th>
                        <th>Dates</th>
                        <th>Reason</th>
                        <th>Created</th>
                    </tr>
                    <?php foreach ($companyMaintenanceRows as $maintenanceRow): ?>
                        <tr>
                            <td><?= e($maintenanceRow['vehicle_name']) ?></td>
                            <td><?= e($maintenanceRow['start_date']) ?> to <?= e($maintenanceRow['end_date']) ?></td>
                            <td><?= e($maintenanceRow['reason']) ?></td>
                            <td><?= e($maintenanceRow['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$companyMaintenanceRows): ?>
                <div class="empty-state"><h3>No maintenance records</h3><p>Agents can add maintenance blackouts from their dashboard.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'bookings'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Company bookings</h2>
                    <p class="muted">Company-level view of customer bookings and payment states.</p>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>User</th>
                        <th>Vehicle</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                    </tr>
                    <?php foreach ($companyBookings as $companyBooking): ?>
                        <tr>
                            <td><?= e($companyBooking['user_name']) ?><br><span class="muted"><?= e($companyBooking['user_email']) ?></span></td>
                            <td><?= e($companyBooking['vehicle_name']) ?></td>
                            <td><?= e($companyBooking['start_date']) ?> to <?= e($companyBooking['end_date']) ?></td>
                            <td><?= e(money($companyBooking['total_price'])) ?></td>
                            <td><span class="<?= e(role_badge($companyBooking['status'])) ?>"><?= e($companyBooking['status']) ?></span></td>
                            <td><span class="<?= e(payment_badge($companyBooking['payment_status'])) ?>"><?= e($companyBooking['payment_status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$companyBookings): ?>
                <div class="empty-state"><h3>No bookings yet</h3><p>Bookings for company vehicles will appear here.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'revenue'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Company revenue</h2>
                    <p class="muted">Only paid bookings for your company are included.</p>
                </div>
                <strong><?= e(money($companyRevenueTotal)) ?></strong>
            </div>
            <form class="search-bar" method="get">
                <input type="hidden" name="section" value="revenue">
                <label><span>Month</span><input type="number" min="1" max="12" name="month" value="<?= e($companyRevenueMonth) ?>"></label>
                <label><span>Year</span><input type="number" min="2020" max="2100" name="year" value="<?= e($companyRevenueYear) ?>"></label>
                <button class="btn" type="submit">Apply</button>
            </form>
            <div class="chart-bars revenue-bars">
                <?php foreach ($companyRevenueRows as $companyRevenueRow): ?>
                    <?php $barHeight = $companyRevenueTotal > 0 ? max(8, (int) (((float) $companyRevenueRow['revenue_total'] / $companyRevenueTotal) * 100)) : 8; ?>
                    <div><span style="height: <?= e($barHeight) ?>%"></span><b><?= e($companyRevenueRow['vehicle_name']) ?></b><small><?= e(money($companyRevenueRow['revenue_total'])) ?></small></div>
                <?php endforeach; ?>
            </div>
            <?php if (!$companyRevenueRows): ?>
                <div class="empty-state"><h3>No paid revenue</h3><p>No paid bookings were found for this month.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php if ($me['role_name'] === 'agent'): ?>
    <?php
    $vehicles = db_all(
        'SELECT v.*, c.name AS category_name, t.name AS type_name
         FROM vehicles v
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         WHERE v.company_id = ?
         ORDER BY v.id DESC',
        [$me['company_id']]
    );
    $bookings = db_all(
        'SELECT b.*, v.name AS vehicle_name, u.name AS user_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users u ON u.id = b.user_id
         WHERE b.company_id = ?
         ORDER BY b.id DESC',
        [$me['company_id']]
    );
    $maintenanceRows = db_all(
        'SELECT m.*, v.name AS vehicle_name
         FROM maintenance m
         JOIN vehicles v ON v.id = m.vehicle_id
         WHERE v.company_id = ?
         ORDER BY m.id DESC',
        [$me['company_id']]
    );

    $pendingCount = 0;
    $approvedCount = 0;

    foreach ($bookings as $bookingCountRow) {
        if ($bookingCountRow['status'] === 'pending') {
            $pendingCount++;
        }

        if ($bookingCountRow['status'] === 'approved') {
            $approvedCount++;
        }
    }
    ?>

    <nav class="dashboard-tabs">
        <a class="<?= $section === 'vehicles' ? 'active' : '' ?>" href="dashboard.php?section=vehicles">Vehicles</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">Bookings</a>
        <a class="<?= $section === 'maintenance' ? 'active' : '' ?>" href="dashboard.php?section=maintenance">Maintenance</a>
        <a href="payments.php">Payments</a>
    </nav>

    <section class="metric-grid">
        <article class="metric-card">
            <strong><?= e(count($vehicles)) ?></strong>
            <span>Company vehicles</span>
        </article>
        <article class="metric-card">
            <strong><?= e($pendingCount) ?></strong>
            <span>Pending requests</span>
        </article>
        <article class="metric-card">
            <strong><?= e($approvedCount) ?></strong>
            <span>Approved bookings</span>
        </article>
        <article class="metric-card">
            <strong><?= e(count($maintenanceRows)) ?></strong>
            <span>Maintenance blocks</span>
        </article>
    </section>

    <?php if ($section === 'maintenance'): ?>
        <form class="box simple-form" action="actions/vehicle.php" method="post">
            <h2>Add maintenance blackout</h2>
            <p class="muted">Block dates when a vehicle is under service so customers cannot book it.</p>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="maintenance">
            <label>Vehicle</label>
            <select name="vehicle_id" required>
                <?php foreach ($vehicles as $v): ?>
                    <option value="<?= e($v['id']) ?>"><?= e($v['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Start date</label>
            <input type="date" name="start_date" required>
            <label>End date</label>
            <input type="date" name="end_date" required>
            <label>Reason</label>
            <input type="text" name="reason" required>
            <button class="btn" type="submit">Add maintenance</button>
        </form>

        <section class="box">
            <h2>Maintenance history</h2>
            <table>
                <tr>
                    <th>Vehicle</th>
                    <th>Dates</th>
                    <th>Reason</th>
                    <th>Created</th>
                </tr>
                <?php foreach ($maintenanceRows as $m): ?>
                    <tr>
                        <td><?= e($m['vehicle_name']) ?></td>
                        <td><?= e($m['start_date']) ?> to <?= e($m['end_date']) ?></td>
                        <td><?= e($m['reason']) ?></td>
                        <td><?= e($m['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php if (!$maintenanceRows): ?>
                <div class="empty-state">
                    <h3>No maintenance records</h3>
                    <p>Add a blackout when a vehicle is not ready for rental.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'vehicles'): ?>
    <section class="box dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h2>Vehicles</h2>
                <p class="muted">Edit, delete, or review fleet details separately from bookings.</p>
            </div>
            <a class="btn" href="dashboard.php?section=vehicles&manage=add">Add vehicle</a>
        </div>
        <?php if ($showVehicleForm || ($_GET['manage'] ?? '') === 'add'): ?>
            <form class="simple-form" action="actions/vehicle.php" method="post" enctype="multipart/form-data">
                <h3><?= $editVehicle ? 'Update vehicle' : 'Add vehicle' ?></h3>
                <p class="muted">Keep vehicle details complete so customers can compare price, category and location clearly.</p>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="vehicle_id" value="<?= e($editVehicle['id'] ?? 0) ?>">

                <label>Name</label>
                <input type="text" name="name" value="<?= e($editVehicle['name'] ?? '') ?>" required>
                <label>Category</label>
                <select name="category_id" data-category-select required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>" <?= (int) ($editVehicle['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Type</label>
                <select name="type_id" data-type-select required>
                    <option value="">Select type</option>
                    <?php foreach ($types as $type): ?>
                        <option
                            value="<?= e($type['id']) ?>"
                            data-category="<?= e($type['category_id']) ?>"
                            <?= (int) ($editVehicle['type_id'] ?? 0) === (int) $type['id'] ? 'selected' : '' ?>
                        >
                            <?= e($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>Location</label>
                <input type="text" name="location" value="<?= e($editVehicle['location'] ?? '') ?>" required>
                <label>Self-drive price / day</label>
                <input type="number" name="self_drive_price" value="<?= e($editVehicle['self_drive_price'] ?? '') ?>" min="1" step="0.01" required>
                <label>With-driver price / day</label>
                <input type="number" name="with_driver_price" value="<?= e($editVehicle['with_driver_price'] ?? '') ?>" min="1" step="0.01" required>
                <label>Status</label>
                <select name="status">
                    <option value="available" <?= ($editVehicle['status'] ?? '') === 'available' ? 'selected' : '' ?>>available</option>
                    <option value="unavailable" <?= ($editVehicle['status'] ?? '') === 'unavailable' ? 'selected' : '' ?>>unavailable</option>
                </select>
                <label>Latitude</label>
                <input type="text" name="latitude" value="<?= e($editVehicle['latitude'] ?? '') ?>">
                <label>Longitude</label>
                <input type="text" name="longitude" value="<?= e($editVehicle['longitude'] ?? '') ?>">
                <label>Image</label>
                <input type="file" name="image">
                <label>Description</label>
                <textarea name="description"><?= e($editVehicle['description'] ?? '') ?></textarea>
                <div class="actions">
                    <button class="btn" type="submit"><?= $editVehicle ? 'Update' : 'Add' ?> vehicle</button>
                    <a class="btn light" href="dashboard.php?section=vehicles">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
        <table>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Location</th>
                <th>Prices</th>
                <th>Status</th>
                <th>GPS</th>
                <th>Action</th>
            </tr>
            <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><?= e($v['name']) ?></td>
                    <td><?= e($v['category_name']) ?> - <?= e($v['type_name']) ?></td>
                    <td><?= e($v['location']) ?></td>
                    <td>Self: <?= e(money($v['self_drive_price'])) ?><br>Driver: <?= e(money($v['with_driver_price'])) ?></td>
                    <td><span class="<?= e(role_badge($v['status'])) ?>"><?= e($v['status']) ?></span></td>
                    <td><?= e($v['latitude'] ?: '-') ?>, <?= e($v['longitude'] ?: '-') ?></td>
                    <td class="actions">
                        <a class="btn tiny" href="dashboard.php?section=vehicles&edit=<?= e($v['id']) ?>">Edit</a>
                        <form action="actions/vehicle.php" method="post" onsubmit="return confirm('Delete vehicle?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="vehicle_id" value="<?= e($v['id']) ?>">
                            <button class="btn danger tiny" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$vehicles): ?>
            <div class="empty-state">
                <h3>No vehicles added</h3>
                <p>Add the first company vehicle from the Vehicles section.</p>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($section === 'bookings'): ?>
    <section class="box dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h2>Booking requests</h2>
                <p class="muted">Review customer documents, approve valid requests, and reject unavailable trips.</p>
            </div>
        </div>
        <table>
            <tr>
                <th>User</th>
                <th>Vehicle</th>
                <th>Dates</th>
                <th>Mode</th>
                <th>Total</th>
                <th>Status</th>
                <th>Files</th>
                <th>Action</th>
            </tr>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= e($b['user_name']) ?></td>
                    <td><?= e($b['vehicle_name']) ?></td>
                    <td><?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></td>
                    <td><?= $b['with_driver'] ? 'With driver' : 'Self drive' ?></td>
                    <td><?= e(money($b['total_price'])) ?></td>
                    <td><span class="<?= e(role_badge($b['status'])) ?>"><?= e($b['status']) ?></span></td>
                    <td>
                        <?php if ($b['with_driver']): ?>
                            Not needed
                        <?php else: ?>
                            <a href="uploads/documents/<?= e($b['document_file']) ?>" target="_blank">ID</a>
                            |
                            <a href="uploads/documents/<?= e($b['license_file']) ?>" target="_blank">License</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($b['status'] === 'pending'): ?>
                            <form class="decision-form" action="actions/booking.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="decide">
                                <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                <input type="text" name="agent_note" placeholder="small note">
                                <button class="btn tiny" name="status" value="approved">Approve</button>
                                <button class="btn danger tiny" name="status" value="rejected">Reject</button>
                            </form>
                        <?php else: ?>
                            Done
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$bookings): ?>
            <div class="empty-state">
                <h3>No booking requests</h3>
                <p>Customer booking requests will appear here.</p>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
