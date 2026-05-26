<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/backend/models/NotificationService.php';
require_login();

$me = current_user();
$pageTitle = 'Dashboard';
$editId = (int) ($_GET['edit'] ?? 0);
$section = $_GET['section'] ?? 'overview';
$editVehicle = null;
$isPlatformAdmin = is_platform_admin_role($me['role_name']);

if ($me['role_name'] === 'agent' && $editId > 0) {
    $editVehicle = db_one('SELECT * FROM vehicles WHERE id = ? AND company_id = ?', [$editId, $me['company_id']]);
}

$categories = db_all('SELECT * FROM vehicle_categories ORDER BY name');
$types = db_all('SELECT * FROM vehicle_types ORDER BY category_id, name');

require __DIR__ . '/includes/header.php';
?>

<section class="section-head">
    <h1><?= e(ucfirst($me['role_name'])) ?> dashboard</h1>
    <p>Welcome back, <?= e($me['name']) ?>.</p>
</section>

<?php if ($section === 'notifications' && $me['role_name'] !== 'user'): ?>
    <?php $roleNotifications = NotificationService::latestForUser((int) $me['id'], 12); ?>
    <section class="box dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h2>Notifications</h2>
                <p class="muted">Recent booking and system updates for your role.</p>
            </div>
            <form action="actions/notification.php" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button class="btn light small" type="submit">Mark all read</button>
            </form>
        </div>
        <div class="notification-list">
            <?php foreach ($roleNotifications as $notice): ?>
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
        <?php if (!$roleNotifications): ?>
            <div class="empty-state"><h3>No notifications</h3><p>Updates for your account will appear here.</p></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

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
        <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="dashboard.php?section=overview">Overview</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">My bookings</a>
        <a class="<?= $section === 'reviews' ? 'active' : '' ?>" href="dashboard.php?section=reviews">Reviews</a>
        <a class="<?= $section === 'notifications' ? 'active' : '' ?>" href="dashboard.php?section=notifications">Notifications</a>
        <a href="vehicles.php">Book vehicle</a>
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

    <?php if ($section === 'overview'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Booking overview</h2>
                    <p class="muted">Your latest rental requests and trip status.</p>
                </div>
                <a class="btn light" href="dashboard.php?section=bookings">Manage bookings</a>
            </div>

            <div class="booking-card-grid">
                <?php foreach ($recentBookings as $b): ?>
                    <article class="booking-summary-card">
                        <div class="card-line">
                            <strong><?= e($b['vehicle_name']) ?></strong>
                            <span class="<?= e(role_badge($b['status'])) ?>"><?= e($b['status']) ?></span>
                        </div>
                        <p><?= e($b['company_name']) ?></p>
                        <p><?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></p>
                        <p><b><?= e(money($b['total_price'])) ?></b> · <?= $b['with_driver'] ? 'With driver' : 'Self drive' ?></p>
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
    $adminSearch = trim((string) ($_GET['search'] ?? ''));
    $adminStatus = trim((string) ($_GET['status'] ?? ''));
    $adminUserId = (int) ($_GET['user_id'] ?? 0);
    $adminStartDate = trim((string) ($_GET['start_date'] ?? ''));
    $adminEndDate = trim((string) ($_GET['end_date'] ?? ''));
    $revenueMonth = (int) ($_GET['month'] ?? date('n'));
    $revenueYear = (int) ($_GET['year'] ?? date('Y'));
    $revenueMonth = $revenueMonth >= 1 && $revenueMonth <= 12 ? $revenueMonth : (int) date('n');
    $revenueYear = $revenueYear >= 2020 && $revenueYear <= 2100 ? $revenueYear : (int) date('Y');
    $revenueStart = sprintf('%04d-%02d-01', $revenueYear, $revenueMonth);
    $revenueEnd = date('Y-m-t', strtotime($revenueStart));

    $bookingWhere = [];
    $bookingParams = [];

    if ($adminSearch !== '') {
        $bookingWhere[] = '(v.name LIKE ? OR renter.name LIKE ? OR renter.email LIKE ? OR company.company_name LIKE ?)';
        $like = '%' . $adminSearch . '%';
        array_push($bookingParams, $like, $like, $like, $like);
    }

    if ($adminStatus !== '' && in_array($adminStatus, ['pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled'], true)) {
        $bookingWhere[] = 'b.status = ?';
        $bookingParams[] = $adminStatus;
    }

    if ($adminUserId > 0) {
        $bookingWhere[] = 'b.user_id = ?';
        $bookingParams[] = $adminUserId;
    }

    if ($adminStartDate !== '') {
        $bookingWhere[] = 'b.start_date >= ?';
        $bookingParams[] = $adminStartDate;
    }

    if ($adminEndDate !== '') {
        $bookingWhere[] = 'b.end_date <= ?';
        $bookingParams[] = $adminEndDate;
    }

    $bookingWhereSql = $bookingWhere ? 'WHERE ' . implode(' AND ', $bookingWhere) : '';

    $adminBookings = db_all(
        'SELECT b.*, v.name AS vehicle_name, renter.name AS user_name, renter.email AS user_email,
                company.company_name, company.name AS company_contact_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users renter ON renter.id = b.user_id
         JOIN users company ON company.id = b.company_id
         ' . $bookingWhereSql . '
         ORDER BY b.created_at DESC, b.id DESC
         LIMIT 100',
        $bookingParams
    );

    $bookingUsers = db_all(
        'SELECT DISTINCT u.id, u.name, u.email
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         ORDER BY u.name'
    );

    $allVehicles = db_all(
        'SELECT v.id, v.name, v.company_id, u.company_name
         FROM vehicles v
         JOIN users u ON u.id = v.company_id
         ORDER BY u.company_name, v.name'
    );

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
    $revenueRows = db_all(
        'SELECT DATE_FORMAT(b.start_date, "%Y-%m") AS revenue_month,
                COALESCE(SUM(b.total_price), 0) AS revenue_total,
                COUNT(*) AS paid_bookings
         FROM bookings b
         WHERE b.payment_status = "paid"
           AND b.start_date BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(b.start_date, "%Y-%m")
         ORDER BY revenue_month',
        [$revenueStart, $revenueEnd]
    );
    $monthlyRevenueTotal = 0;
    foreach ($revenueRows as $revenueRow) {
        $monthlyRevenueTotal += (float) $revenueRow['revenue_total'];
    }

    $requests = db_all(
        'SELECT cr.*, u.name, u.email, u.phone, u.company_name, u.address, u.status AS company_status
         FROM company_requests cr
         JOIN users u ON u.id = cr.company_id
         ORDER BY cr.status = "pending" DESC, cr.id DESC'
    );

    $companies = db_all(
        'SELECT u.*
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE r.name = "company"
         ORDER BY u.id DESC'
    );
    ?>

    <nav class="dashboard-tabs">
        <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="dashboard.php?section=overview">Analytics</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">All bookings</a>
        <a class="<?= $section === 'revenue' ? 'active' : '' ?>" href="dashboard.php?section=revenue">Revenue</a>
        <a class="<?= $section === 'companies' ? 'active' : '' ?>" href="dashboard.php?section=companies">Companies</a>
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

    <?php if ($section === 'overview'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Platform analytics</h2>
                    <p class="muted">Bookings, paid revenue, and public website rating health.</p>
                </div>
                <a class="btn light" href="dashboard.php?section=bookings">Manage bookings</a>
            </div>
            <div class="chart-bars">
                <div><span style="height: <?= e(min(100, max(8, (int) ($adminStats['pending_bookings'] ?? 0) * 12))) ?>%"></span><b>Pending</b></div>
                <div><span style="height: <?= e(min(100, max(8, (int) ($adminStats['active_bookings'] ?? 0) * 12))) ?>%"></span><b>Active</b></div>
                <div><span style="height: <?= e(min(100, max(8, (int) ($ratingStats['average_rating'] * 20)))) ?>%"></span><b>Rating</b></div>
            </div>
            <h3>Recent service ratings</h3>
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

    <?php if ($section === 'bookings'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>All bookings</h2>
                    <p class="muted">Search, filter, modify, or cancel any booking on the platform.</p>
                </div>
            </div>
            <form class="search-bar admin-filter" method="get">
                <input type="hidden" name="section" value="bookings">
                <label><span>Search</span><input type="text" name="search" value="<?= e($adminSearch) ?>" placeholder="vehicle, user, company"></label>
                <label><span>Status</span>
                    <select name="status">
                        <option value="">All</option>
                        <?php foreach (['pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled'] as $statusOption): ?>
                            <option value="<?= e($statusOption) ?>" <?= $adminStatus === $statusOption ? 'selected' : '' ?>><?= e($statusOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>User</span>
                    <select name="user_id">
                        <option value="0">All users</option>
                        <?php foreach ($bookingUsers as $bookingUser): ?>
                            <option value="<?= e($bookingUser['id']) ?>" <?= $adminUserId === (int) $bookingUser['id'] ? 'selected' : '' ?>><?= e($bookingUser['name'] . ' - ' . $bookingUser['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Start after</span><input type="date" name="start_date" value="<?= e($adminStartDate) ?>"></label>
                <label><span>End before</span><input type="date" name="end_date" value="<?= e($adminEndDate) ?>"></label>
                <button class="btn" type="submit">Filter</button>
            </form>

            <div class="table-scroll">
                <table>
                    <tr>
                        <th>User</th>
                        <th>Vehicle</th>
                        <th>Company</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Modify</th>
                    </tr>
                    <?php foreach ($adminBookings as $bookingRow): ?>
                        <tr>
                            <td><?= e($bookingRow['user_name']) ?><br><span class="muted"><?= e($bookingRow['user_email']) ?></span></td>
                            <td><?= e($bookingRow['vehicle_name']) ?></td>
                            <td><?= e($bookingRow['company_name'] ?: $bookingRow['company_contact_name']) ?></td>
                            <td><?= e($bookingRow['start_date']) ?> to <?= e($bookingRow['end_date']) ?></td>
                            <td><?= e(money($bookingRow['total_price'])) ?></td>
                            <td><span class="<?= e(role_badge($bookingRow['status'])) ?>"><?= e($bookingRow['status']) ?></span></td>
                            <td><span class="<?= e(payment_badge($bookingRow['payment_status'])) ?>"><?= e($bookingRow['payment_status']) ?></span></td>
                            <td>
                                <form class="admin-booking-form" action="actions/booking.php" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="admin_update">
                                    <input type="hidden" name="booking_id" value="<?= e($bookingRow['id']) ?>">
                                    <select name="vehicle_id" required>
                                        <?php foreach ($allVehicles as $vehicleOption): ?>
                                            <option value="<?= e($vehicleOption['id']) ?>" <?= (int) $bookingRow['vehicle_id'] === (int) $vehicleOption['id'] ? 'selected' : '' ?>><?= e($vehicleOption['name'] . ' - ' . $vehicleOption['company_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="date" name="start_date" value="<?= e($bookingRow['start_date']) ?>" required>
                                    <input type="date" name="end_date" value="<?= e($bookingRow['end_date']) ?>" required>
                                    <label class="check-line compact"><input type="checkbox" name="with_driver" value="1" <?= $bookingRow['with_driver'] ? 'checked' : '' ?>> Driver</label>
                                    <select name="status" required>
                                        <?php foreach (['pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled'] as $statusOption): ?>
                                            <option value="<?= e($statusOption) ?>" <?= $bookingRow['status'] === $statusOption ? 'selected' : '' ?>><?= e($statusOption) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="payment_status" required>
                                        <?php foreach (['cash_due', 'pending', 'paid', 'failed', 'refunded'] as $payOption): ?>
                                            <option value="<?= e($payOption) ?>" <?= $bookingRow['payment_status'] === $payOption ? 'selected' : '' ?>><?= e($payOption) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="agent_note" value="<?= e($bookingRow['agent_note']) ?>" placeholder="note">
                                    <button class="btn tiny" type="submit">Save</button>
                                </form>
                                <form action="actions/booking.php" method="post" onsubmit="return confirm('Cancel this booking?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="admin_cancel">
                                    <input type="hidden" name="booking_id" value="<?= e($bookingRow['id']) ?>">
                                    <button class="btn danger tiny" type="submit">Cancel</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if (!$adminBookings): ?>
                <div class="empty-state"><h3>No bookings found</h3><p>Try changing the filters.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'revenue'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Monthly revenue</h2>
                    <p class="muted">Platform revenue is calculated only from bookings marked paid.</p>
                </div>
                <strong><?= e(money($monthlyRevenueTotal)) ?></strong>
            </div>
            <form class="search-bar" method="get">
                <input type="hidden" name="section" value="revenue">
                <label><span>Month</span><input type="number" name="month" min="1" max="12" value="<?= e($revenueMonth) ?>"></label>
                <label><span>Year</span><input type="number" name="year" min="2020" max="2100" value="<?= e($revenueYear) ?>"></label>
                <button class="btn" type="submit">Apply</button>
            </form>
            <div class="chart-bars revenue-bars">
                <?php foreach ($revenueRows as $revenueRow): ?>
                    <?php $barHeight = $monthlyRevenueTotal > 0 ? max(8, (int) (((float) $revenueRow['revenue_total'] / $monthlyRevenueTotal) * 100)) : 8; ?>
                    <div><span style="height: <?= e($barHeight) ?>%"></span><b><?= e($revenueRow['revenue_month']) ?></b><small><?= e(money($revenueRow['revenue_total'])) ?></small></div>
                <?php endforeach; ?>
            </div>
            <?php if (!$revenueRows): ?>
                <div class="empty-state"><h3>No paid revenue</h3><p>No paid bookings were found for this month.</p></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'companies'): ?>
    <section class="box">
        <h2>Company approval requests</h2>
        <table>
            <tr>
                <th>Company</th>
                <th>Request</th>
                <th>Requested data</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($requests as $request): ?>
                <?php
                $data = json_decode($request['requested_data'] ?? '', true);
                if (!is_array($data)) {
                    $data = [];
                }
                ?>
                <tr>
                    <td>
                        <?= e($request['company_name'] ?: $request['name']) ?><br>
                        <span class="muted"><?= e($request['email']) ?></span>
                    </td>
                    <td><?= e($request['request_type']) ?></td>
                    <td>
                        <?php if ($data): ?>
                            Name: <?= e($data['company_name'] ?? '-') ?><br>
                            Phone: <?= e($data['phone'] ?? '-') ?><br>
                            Address: <?= e($data['address'] ?? '-') ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><span class="<?= e(role_badge($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                    <td>
                        <?php if ($request['status'] === 'pending'): ?>
                            <form class="decision-form" action="actions/auth.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="review_company_request">
                                <input type="hidden" name="request_id" value="<?= e($request['id']) ?>">
                                <input type="text" name="admin_note" placeholder="admin note">
                                <button class="btn tiny" name="decision" value="approved">Approve</button>
                                <button class="btn danger tiny" name="decision" value="rejected">Reject</button>
                            </form>
                        <?php else: ?>
                            Reviewed
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$requests): ?>
            <p>No company requests yet.</p>
        <?php endif; ?>
    </section>

    <section class="box">
        <h2>Companies</h2>
        <table>
            <tr>
                <th>Company</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?= e($company['company_name'] ?: $company['name']) ?></td>
                    <td><?= e($company['email']) ?></td>
                    <td><?= e($company['phone']) ?></td>
                    <td><span class="<?= e(role_badge($company['status'])) ?>"><?= e($company['status']) ?></span></td>
                    <td>
                        <?php if ($company['status'] !== 'inactive'): ?>
                            <form action="actions/auth.php" method="post" onsubmit="return confirm('Deactivate this company?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="admin_delete_company">
                                <input type="hidden" name="company_id" value="<?= e($company['id']) ?>">
                                <button class="btn danger tiny" type="submit">Delete</button>
                            </form>
                        <?php else: ?>
                            Inactive
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
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
    $agents = db_all('SELECT * FROM users WHERE company_id = ? ORDER BY id DESC', [$me['id']]);
    $companyRequests = db_all('SELECT * FROM company_requests WHERE company_id = ? ORDER BY id DESC', [$me['id']]);
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
        <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="dashboard.php?section=overview">Overview</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">Bookings</a>
        <a class="<?= $section === 'revenue' ? 'active' : '' ?>" href="dashboard.php?section=revenue">Revenue</a>
        <a class="<?= $section === 'maintenance' ? 'active' : '' ?>" href="dashboard.php?section=maintenance">Maintenance</a>
    </nav>

    <section class="metric-grid">
        <article class="metric-card"><strong><?= e(count($vehicles)) ?></strong><span>Vehicles</span></article>
        <article class="metric-card"><strong><?= e(count($agents)) ?></strong><span>Agents</span></article>
        <article class="metric-card"><strong><?= e(count($companyBookings)) ?></strong><span>Bookings</span></article>
        <article class="metric-card"><strong><?= e(money($companyRevenueTotal)) ?></strong><span>Monthly paid revenue</span></article>
    </section>

    <?php if ($section === 'overview'): ?>
    <section class="grid two">
        <form class="box simple-form" action="actions/auth.php" method="post">
            <h2>Company profile</h2>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_company">
            <label>Company name</label>
            <input type="text" name="company_name" value="<?= e($me['company_name']) ?>" required>
            <label>Phone</label>
            <input type="text" name="phone" value="<?= e($me['phone']) ?>" required>
            <label>Address</label>
            <input type="text" name="address" value="<?= e($me['address']) ?>">
            <button class="btn" type="submit">Send update request</button>
        </form>

        <form class="box simple-form" action="actions/auth.php" method="post">
            <h2>Add agent</h2>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_agent">
            <label>Agent name</label>
            <input type="text" name="name" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Phone</label>
            <input type="text" name="phone" required>
            <label>Password</label>
            <input type="password" name="password" minlength="6" required>
            <button class="btn" type="submit">Create agent</button>
        </form>
    </section>

    <section class="box">
        <h2>Company approval requests</h2>
        <table>
            <tr>
                <th>Type</th>
                <th>Status</th>
                <th>Admin note</th>
                <th>Date</th>
            </tr>
            <?php foreach ($companyRequests as $request): ?>
                <tr>
                    <td><?= e($request['request_type']) ?></td>
                    <td><span class="<?= e(role_badge($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                    <td><?= e($request['admin_note'] ?: '-') ?></td>
                    <td><?= e($request['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$companyRequests): ?>
            <p>No company requests yet.</p>
        <?php endif; ?>

        <form action="actions/auth.php" method="post" onsubmit="return confirm('Request admin to delete this company?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_company_delete">
            <button class="btn danger" type="submit">Request company deletion</button>
        </form>
    </section>

    <section class="box">
        <h2>Agents</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            <?php foreach ($agents as $a): ?>
                <tr>
                    <td colspan="4">
                        <form class="decision-form" action="actions/auth.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="agent_id" value="<?= e($a['id']) ?>">
                            <input type="text" name="name" value="<?= e($a['name']) ?>" required>
                            <span><?= e($a['email']) ?></span>
                            <input type="text" name="phone" value="<?= e($a['phone']) ?>" required>
                            <button class="btn tiny" name="action" value="edit_agent">Save</button>
                            <button class="btn danger tiny" name="action" value="delete_agent" onclick="return confirm('Delete agent?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (!$agents): ?>
            <p>No agents added yet.</p>
        <?php endif; ?>
    </section>

    <section class="box">
        <h2>Company vehicles summary</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Location</th>
                <th>Self drive</th>
                <th>With driver</th>
                <th>Status</th>
            </tr>
            <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><?= e($v['name']) ?></td>
                    <td><?= e($v['category_name']) ?> - <?= e($v['type_name']) ?></td>
                    <td><?= e($v['location']) ?></td>
                    <td><?= e(money($v['self_drive_price'])) ?></td>
                    <td><?= e(money($v['with_driver_price'])) ?></td>
                    <td><span class="<?= e(role_badge($v['status'])) ?>"><?= e($v['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
    <?php endif; ?>

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
    if ($editVehicle) {
        $section = 'add_vehicle';
    }

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
        <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="dashboard.php?section=overview">Overview</a>
        <a class="<?= $section === 'add_vehicle' ? 'active' : '' ?>" href="dashboard.php?section=add_vehicle">Add vehicle</a>
        <a class="<?= $section === 'maintenance' ? 'active' : '' ?>" href="dashboard.php?section=maintenance">Maintenance</a>
        <a class="<?= $section === 'vehicles' ? 'active' : '' ?>" href="dashboard.php?section=vehicles">Vehicles</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="dashboard.php?section=bookings">Booking requests</a>
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

    <?php if ($section === 'overview'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Agent overview</h2>
                    <p class="muted">Quick view of fleet work, maintenance and booking requests.</p>
                </div>
                <a class="btn" href="dashboard.php?section=add_vehicle">Add vehicle</a>
            </div>

            <div class="booking-card-grid">
                <?php foreach (array_slice($bookings, 0, 3) as $b): ?>
                    <article class="booking-summary-card">
                        <div class="card-line">
                            <strong><?= e($b['vehicle_name']) ?></strong>
                            <span class="<?= e(role_badge($b['status'])) ?>"><?= e($b['status']) ?></span>
                        </div>
                        <p><?= e($b['user_name']) ?></p>
                        <p><?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></p>
                        <p><b><?= e(money($b['total_price'])) ?></b> · <?= $b['with_driver'] ? 'With driver' : 'Self drive' ?></p>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (!$bookings): ?>
                <div class="empty-state">
                    <h3>No booking requests yet</h3>
                    <p>New customer bookings will appear here for approval.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'add_vehicle'): ?>
        <form class="box simple-form" action="actions/vehicle.php" method="post" enctype="multipart/form-data">
            <h2><?= $editVehicle ? 'Update vehicle' : 'Add vehicle' ?></h2>
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
            <button class="btn" type="submit"><?= $editVehicle ? 'Update' : 'Add' ?> vehicle</button>
            <?php if ($editVehicle): ?>
                <a class="btn light" href="dashboard.php?section=vehicles">Cancel edit</a>
            <?php endif; ?>
        </form>
    <?php endif; ?>

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
            <a class="btn" href="dashboard.php?section=add_vehicle">Add vehicle</a>
        </div>
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
                        <a class="btn tiny" href="dashboard.php?section=add_vehicle&edit=<?= e($v['id']) ?>">Edit</a>
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
                <p>Add the first company vehicle from the Add vehicle tab.</p>
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
