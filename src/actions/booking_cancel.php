<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role('user');
require_csrf();

$viewer = current_user();
$bookingId = (int) ($_POST['booking_id'] ?? 0);

if ($bookingId < 1) {
    set_flash('Invalid booking cancellation request.', 'danger');
    redirect('dashboard.php?section=bookings');
}

$booking = db_one(
    'SELECT * FROM bookings WHERE id = ? AND user_id = ?',
    [$bookingId, (int) ($viewer['id'] ?? 0)]
);

if (!$booking) {
    set_flash('Booking not found.', 'danger');
    redirect('dashboard.php?section=bookings');
}

if ($booking['status'] !== 'pending') {
    set_flash('Only pending bookings can be cancelled by the user.', 'warning');
    redirect('dashboard.php?section=bookings');
}

require_db()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['cancelled', $bookingId]);

set_flash('Booking request cancelled.', 'success');
redirect('dashboard.php?section=bookings');


