<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role(['super_admin', 'company', 'agent']);
require_csrf();

$viewer = current_user();
$companyId = managed_company_id();
$bookingId = (int) ($_POST['booking_id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($bookingId < 1 || !in_array($status, ['confirmed', 'cancelled'], true)) {
    set_flash('Invalid booking update request.', 'danger');
    redirect('dashboard.php?section=bookings');
}

$booking = db_one('SELECT * FROM bookings WHERE id = ?', [$bookingId]);

if (!$booking) {
    set_flash('Booking not found.', 'danger');
    redirect('dashboard.php?section=bookings');
}

if (in_array($viewer['role'], ['company', 'agent'], true) && (int) $booking['company_id'] !== $companyId) {
    set_flash('You cannot update a booking outside your company.', 'danger');
    redirect('dashboard.php?section=bookings');
}

if ($status === 'confirmed') {
    $overlap = db_one(
        "SELECT id FROM bookings
         WHERE vehicle_id = ?
           AND id <> ?
           AND status IN ('pending', 'confirmed')
           AND ? < end_datetime
           AND ? > start_datetime
         LIMIT 1",
        [
            (int) $booking['vehicle_id'],
            $bookingId,
            $booking['start_datetime'],
            $booking['end_datetime'],
        ]
    );

    if ($overlap) {
        set_flash('This booking overlaps with another active booking.', 'danger');
        redirect('dashboard.php?section=bookings');
    }

    $blocked = db_one(
        'SELECT id FROM availability_blocks
         WHERE vehicle_id = ?
           AND ? < end_datetime
           AND ? > start_datetime
         LIMIT 1',
        [
            (int) $booking['vehicle_id'],
            $booking['start_datetime'],
            $booking['end_datetime'],
        ]
    );

    if ($blocked) {
        set_flash('The vehicle is blocked in that period, so this booking cannot be confirmed.', 'danger');
        redirect('dashboard.php?section=bookings');
    }
}

$agentId = $booking['agent_id'];
if ($viewer['role'] === 'agent' && isset($viewer['agent_id'])) {
    $agentId = (int) $viewer['agent_id'];
}

require_db()->prepare(
    'UPDATE bookings SET status = ?, notes = ?, agent_id = ? WHERE id = ?'
)->execute([
    $status,
    $notes,
    $agentId,
    $bookingId,
]);

set_flash('Booking status updated.', 'success');
redirect('dashboard.php?section=bookings');


