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
           AND b.status = "completed"
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
    $selectedBooking = null;
    $selectedBookingReview = null;

    if ($section === 'booking_detail' && $selectedBookingId > 0) {
        $selectedBooking = db_one(
            'SELECT b.*, v.name AS vehicle_name, u.company_name
             FROM bookings b
             JOIN vehicles v ON v.id = b.vehicle_id
             JOIN users u ON u.id = b.company_id
             WHERE b.id = ? AND b.user_id = ?
             LIMIT 1',
            [$selectedBookingId, $me['id']]
        );

        if ($selectedBooking) {
            $selectedBookingReview = db_one(
                'SELECT * FROM reviews WHERE booking_id = ? AND user_id = ? LIMIT 1',
                [$selectedBookingId, $me['id']]
            );
        }
    }

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
                        <a class="btn light tiny" href="dashboard.php?section=booking_detail&booking_id=<?= e($b['id']) ?>">Details</a>
                        <p><b><?= e(money($b['total_price'])) ?></b> Â| <?= $b['with_driver'] ? 'With driver' : 'Self drive' ?></p>
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
                                <p><?= e($b['company_name']) ?> Â| <?= $b['with_driver'] ? 'With driver' : 'Self drive' ?></p>
                            </div>
                            <span class="<?= e(role_badge($b['status'])) ?>"><?= e($b['status']) ?></span>
                        </div>

                        <div class="booking-row-details">
                            <span><b>Dates</b><?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></span>
                            <span><b>Total</b><?= e(money($b['total_price'])) ?></span>
                            <span><b>Payment</b><span class="<?= e(payment_badge($bookingPaymentStatus)) ?>"><?= e($bookingPaymentStatus) ?></span></span>
                            <span><b>Note</b><?= e($b['agent_note'] ?: 'No note') ?></span>
                            <span><b>Details</b><a class="btn light tiny" href="dashboard.php?section=booking_detail&booking_id=<?= e($b['id']) ?>">Open booking</a></span>
                        </div>

                        <?php if (in_array($b['status'], ['pending', 'approved', 'confirmed'], true)): ?>
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

                                <?php if (in_array($b['status'], ['approved', 'confirmed'], true)): ?>
                                <form action="actions/booking.php" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="extend">
                                    <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                    <label>
                                        <span>Extend until (max 2 days)</span>
                                        <input type="date" name="end_date" value="<?= e(date('Y-m-d', strtotime($b['end_date'] . ' +1 day'))) ?>" min="<?= e(date('Y-m-d', strtotime($b['end_date'] . ' +1 day'))) ?>" max="<?= e(date('Y-m-d', strtotime($b['end_date'] . ' +2 days'))) ?>" required>
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
                                                    <?= e($option['name'] . ' Â| ' . $option['category_name'] . ' Â| ' . $option['location'] . ' Â| ' . money($option['self_drive_price'])) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <button class="btn small" type="submit">Change</button>
                                </form>
                                <?php endif; ?>

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

    <?php if ($section === 'booking_detail'): ?>
        <section class="box dashboard-panel">
            <?php if (!$selectedBooking): ?>
                <div class="empty-state">
                    <h3>Booking not found</h3>
                    <p>This booking could not be found for your account.</p>
                    <a class="btn light" href="dashboard.php?section=bookings">Back to bookings</a>
                </div>
            <?php else: ?>
                <div class="panel-title-row">
                    <div>
                        <h2>Booking #<?= e($selectedBooking['id']) ?></h2>
                        <p class="muted"><?= e($selectedBooking['vehicle_name']) ?> from <?= e($selectedBooking['company_name']) ?></p>
                    </div>
                    <a class="btn light" href="dashboard.php?section=bookings">Back to bookings</a>
                </div>

                <div class="booking-row-details">
                    <span><b>Status</b><span class="<?= e(role_badge($selectedBooking['status'])) ?>"><?= e($selectedBooking['status']) ?></span></span>
                    <span><b>Payment</b><span class="<?= e(payment_badge($selectedBooking['payment_status'])) ?>"><?= e($selectedBooking['payment_status']) ?></span></span>
                    <span><b>Dates</b><?= e($selectedBooking['start_date']) ?> to <?= e($selectedBooking['end_date']) ?></span>
                    <span><b>Total</b><?= e(money($selectedBooking['total_price'])) ?></span>
                    <span><b>Mode</b><?= $selectedBooking['with_driver'] ? 'With driver' : 'Self drive' ?></span>
                    <span><b>Note</b><?= e($selectedBooking['agent_note'] ?: 'No note') ?></span>
                </div>

                <section class="review-detail-box">
                    <h3>Post-rental review</h3>
                    <?php if ($selectedBookingReview): ?>
                        <p class="muted">You already submitted a review for this booking.</p>
                        <form class="simple-form">
                            <label>Rating</label>
                            <select disabled>
                                <option><?= e($selectedBookingReview['rating']) ?> star(s)</option>
                            </select>
                            <label>Comment</label>
                            <textarea disabled><?= e($selectedBookingReview['comment'] ?: 'No written comment.') ?></textarea>
                            <button class="btn small" type="button" disabled>Review submitted</button>
                        </form>
                    <?php elseif (user_can_review_booking($selectedBooking)): ?>
                        <form class="simple-form" action="actions/review.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="booking_id" value="<?= e($selectedBooking['id']) ?>">
                            <label>Rating</label>
                            <div class="star-selector">
                                <?php for ($ratingOption = 5; $ratingOption >= 1; $ratingOption--): ?>
                                    <label>
                                        <input type="radio" name="rating" value="<?= e($ratingOption) ?>" required>
                                        <span><?= str_repeat('&#9733;', $ratingOption) ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <label>Comment</label>
                            <textarea name="comment" maxlength="1000" placeholder="Share your rental experience"></textarea>
                            <button class="btn small" type="submit">Submit review</button>
                        </form>
                    <?php elseif ($selectedBooking['status'] !== 'completed'): ?>
                        <p class="muted">Review form appears after this rental is marked completed.</p>
                    <?php elseif ($selectedBooking['payment_status'] !== 'paid'): ?>
                        <p class="muted">Review form appears after payment is completed.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($section === 'reviews'): ?>
        <section class="box dashboard-panel">
            <div class="panel-title-row">
                <div>
                    <h2>Reviews</h2>
                    <p class="muted">Reviews are allowed only after a paid rental is marked completed.</p>
                </div>
            </div>

            <div class="review-grid">
                <?php foreach ($reviewableBookings as $b): ?>
                    <article class="review-card">
                        <h3><?= e($b['vehicle_name']) ?></h3>
                        <p><?= e($b['company_name']) ?> Â| <?= e($b['start_date']) ?> to <?= e($b['end_date']) ?></p>
                        <a class="btn small" href="dashboard.php?section=booking_detail&booking_id=<?= e($b['id']) ?>">Review booking</a>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (!$reviewableBookings): ?>
                <div class="empty-state">
                    <h3>No rentals ready for review</h3>
                    <p>A booking becomes reviewable after payment is successful and the rental is marked completed.</p>
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
                            <p><?= e($reminder['vehicle_name']) ?> Â| <?= e($reminder['start_date']) ?> to <?= e($reminder['end_date']) ?></p>
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
                        <small class="muted"><?= e($notice['created_at']) ?> Â| <?= e($notice['type']) ?></small>
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


