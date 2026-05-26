<?php
require_once __DIR__ . '/../includes/functions.php';

check_csrf();
require_role('user');

$action = $_POST['action'] ?? '';
$me = current_user();

if ($action === 'create') {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    $booking = db_one(
        'SELECT * FROM bookings WHERE id = ? AND user_id = ?',
        [$bookingId, $me['id']]
    );

    if (!$booking) {
        flash('Booking not found for review.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if (!user_can_review_booking($booking)) {
        flash('You can review only after a paid rental is completed.', 'danger');
        go('../dashboard.php?section=booking_detail&booking_id=' . $bookingId);
    }

    if ($rating < 1 || $rating > 5) {
        flash('Rating must be between 1 and 5.', 'danger');
        go('../dashboard.php?section=booking_detail&booking_id=' . $bookingId);
    }

    if (db_one('SELECT id FROM reviews WHERE booking_id = ?', [$bookingId])) {
        flash('This booking already has a review.', 'warning');
        go('../dashboard.php?section=booking_detail&booking_id=' . $bookingId);
    }

    db_run(
        'INSERT INTO reviews (booking_id, vehicle_id, user_id, rating, comment)
         VALUES (?, ?, ?, ?, ?)',
        [$bookingId, $booking['vehicle_id'], $me['id'], $rating, $comment]
    );

    flash('Review submitted successfully.', 'success');
    go('../dashboard.php?section=booking_detail&booking_id=' . $bookingId);
}

if ($action === 'site_rating') {
    $rating = (int) ($_POST['rating'] ?? 0);
    $feedback = trim((string) ($_POST['feedback'] ?? ''));

    if ($rating < 1 || $rating > 5) {
        flash('Website rating must be between 1 and 5.', 'danger');
        go('../dashboard.php?section=reviews');
    }

    if (!user_can_rate_site((int) $me['id'])) {
        flash('You have already rated the website.', 'warning');
        go('../dashboard.php?section=reviews');
    }

    db_run(
        'INSERT INTO site_reviews (user_id, rating, feedback) VALUES (?, ?, ?)',
        [(int) $me['id'], $rating, $feedback]
    );

    flash('Thank you for rating the service.', 'success');
    go('../dashboard.php?section=reviews');
}

go('../dashboard.php?section=reviews');
