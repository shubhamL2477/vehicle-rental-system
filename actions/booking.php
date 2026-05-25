<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../backend/models/BookingModel.php';
require_once __DIR__ . '/../backend/models/StripePaymentModel.php';
require_once __DIR__ . '/../backend/models/NotificationService.php';

check_csrf();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    require_role('user');
    $me = current_user();
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';
    $withDriver = isset($_POST['with_driver']) ? 1 : 0;
    $paymentMethod = trim((string) ($_POST['payment_method'] ?? 'cash'));
    $pickupLocation = trim((string) ($_POST['pickup_location'] ?? ''));
    $destination = trim((string) ($_POST['destination'] ?? ''));
    $termsAccepted = isset($_POST['terms_accepted']);

    if (!in_array($paymentMethod, ['cash', 'stripe'], true)) {
        flash('Choose cash or Stripe payment.', 'danger');
        go('../vehicle.php?id=' . $vehicleId);
    }

    if (!$termsAccepted) {
        flash('Please accept booking terms before submitting.', 'danger');
        go('../vehicle.php?id=' . $vehicleId);
    }

    if ($paymentMethod === 'stripe') {
        try {
            StripePaymentModel::requireCheckoutConfig();
        } catch (Throwable $throwable) {
            flash($throwable->getMessage(), 'danger');
            go('../vehicle.php?id=' . $vehicleId);
        }
    }

    $vehicle = db_one('SELECT * FROM vehicles WHERE id = ? AND status = "available"', [$vehicleId]);
    if (!$vehicle || $start === '' || $end === '' || strtotime($end) < strtotime($start)) {
        flash('Booking details are not valid.', 'danger');
        go('../vehicles.php');
    }

    $taken = db_one(
        'SELECT id FROM bookings
         WHERE vehicle_id = ? AND status IN ("pending", "approved", "confirmed")
         AND ? <= end_date AND ? >= start_date',
        [$vehicleId, $start, $end]
    );

    if ($taken) {
        flash('Vehicle already has a booking for selected dates.', 'danger');
        go('../vehicle.php?id=' . $vehicleId);
    }

    $blocked = db_one(
        'SELECT id FROM maintenance
         WHERE vehicle_id = ? AND ? <= end_date AND ? >= start_date',
        [$vehicleId, $start, $end]
    );

    if ($blocked) {
        flash('Vehicle is under maintenance for selected dates.', 'danger');
        go('../vehicle.php?id=' . $vehicleId);
    }

    $availabilityBlocked = db_one(
        'SELECT id FROM availability_blocks
         WHERE vehicle_id = ? AND ? <= DATE(end_datetime) AND ? >= DATE(start_datetime)',
        [$vehicleId, $start, $end]
    );

    if ($availabilityBlocked) {
        flash('Vehicle is blocked for selected dates.', 'danger');
        go('../vehicle.php?id=' . $vehicleId);
    }

    $document = null;
    $license = null;

    if (!$withDriver) {
        $document = save_upload('document_file', DOCUMENT_UPLOAD_PATH);
        $license = save_upload('license_file', DOCUMENT_UPLOAD_PATH);

        if ($document === '' || $license === '') {
            flash('Citizenship/passport and license files are required for self-drive.', 'danger');
            go('../vehicle.php?id=' . $vehicleId);
        }
    }

    $days = booking_days($start, $end);
    $dailyRate = $withDriver
        ? (float) ($vehicle['with_driver_price'] ?? 0)
        : (float) ($vehicle['self_drive_price'] ?? 0);

    if ($dailyRate <= 0) {
        flash('Vehicle price is not configured. Please contact the company.', 'danger');
        go('../vehicle.php?id=' . $vehicleId);
    }

    $total = $days * (float) $dailyRate;

    $paymentStatus = $paymentMethod === 'stripe' ? 'pending' : 'cash_due';

    db_run(
        'INSERT INTO bookings
         (user_id, vehicle_id, company_id, start_datetime, end_datetime, start_date, end_date, pickup_location, destination,
          with_driver, daily_rate, document_file, license_file, total_price, payment_method, payment_status, terms_accepted)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
        [
            $me['id'],
            $vehicleId,
            $vehicle['company_id'],
            booking_start_datetime($start),
            booking_end_datetime($end),
            $start,
            $end,
            $pickupLocation,
            $destination,
            $withDriver,
            $dailyRate,
            $document,
            $license,
            $total,
            $paymentMethod,
            $paymentStatus
        ]
    );

    $bookingId = (int) db()->lastInsertId();
    NotificationService::notifyBookingSubmitted($bookingId);

    if ($paymentMethod === 'stripe') {
        try {
            $booking = BookingModel::findConfirmation($bookingId);
            $session = StripePaymentModel::createCheckoutSession($booking);
            StripePaymentModel::storeCheckoutSession($bookingId, $session);

            if (!empty($session->url)) {
                go((string) $session->url);
            }
        } catch (Throwable $throwable) {
            db_run('UPDATE bookings SET payment_status = "failed" WHERE id = ?', [$bookingId]);
            flash('Booking was created, but Stripe checkout could not start: ' . $throwable->getMessage(), 'danger');
            go('../payment-status.php?booking_id=' . $bookingId);
        }
    }

    flash('Booking request sent to agent.', 'success');
    go('../dashboard.php');
}

if ($action === 'extend') {
    require_role('user');
    $me = current_user();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $newEnd = $_POST['end_date'] ?? '';

    $booking = db_one(
        'SELECT b.*, v.self_drive_price, v.with_driver_price, v.status AS vehicle_status
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.id = ? AND b.user_id = ?',
        [$bookingId, $me['id']]
    );

    if (!$booking || !in_array($booking['status'], ['pending', 'approved'], true)) {
        flash('Only active bookings can be extended.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if ($newEnd === '' || strtotime($newEnd) <= strtotime($booking['end_date'])) {
        flash('Choose an end date later than the current end date.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if (vehicle_unavailable_reason($booking['vehicle_id'], $booking['start_date'], $newEnd, $bookingId) !== '') {
        flash('Cannot extend because the vehicle is unavailable for the new dates.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    $total = booking_total($booking, $booking['start_date'], $newEnd, (bool) $booking['with_driver']);

    db_run(
        'UPDATE bookings
         SET end_date = ?, end_datetime = ?, total_price = ?, status = "pending", agent_note = ?
         WHERE id = ?',
        [$newEnd, booking_end_datetime($newEnd), $total, 'User requested booking extension. Waiting for agent approval.', $bookingId]
    );

    flash('Booking extension requested. Agent approval is required again.', 'success');
    go('../dashboard.php?section=bookings');
}

if ($action === 'change_vehicle') {
    require_role('user');
    $me = current_user();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

    $booking = db_one('SELECT * FROM bookings WHERE id = ? AND user_id = ?', [$bookingId, $me['id']]);
    $vehicle = db_one('SELECT * FROM vehicles WHERE id = ? AND status = "available"', [$vehicleId]);

    if (!$booking || !$vehicle || !in_array($booking['status'], ['pending', 'approved'], true)) {
        flash('Vehicle change request is not valid.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if (vehicle_unavailable_reason($vehicleId, $booking['start_date'], $booking['end_date'], $bookingId) !== '') {
        flash('Selected vehicle is unavailable for the booking dates.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    $total = booking_total($vehicle, $booking['start_date'], $booking['end_date'], (bool) $booking['with_driver']);

    db_run(
        'UPDATE bookings
         SET vehicle_id = ?, company_id = ?, agent_id = NULL, daily_rate = ?, total_price = ?, status = "pending", agent_note = ?
         WHERE id = ?',
        [
            $vehicleId,
            $vehicle['company_id'],
            $booking['with_driver'] ? $vehicle['with_driver_price'] : $vehicle['self_drive_price'],
            $total,
            'User requested vehicle change. Waiting for agent approval.',
            $bookingId
        ]
    );

    flash('Vehicle change requested. Agent approval is required again.', 'success');
    go('../dashboard.php?section=bookings');
}

if ($action === 'cancel') {
    require_role('user');
    $me = current_user();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    $booking = db_one('SELECT * FROM bookings WHERE id = ? AND user_id = ?', [$bookingId, $me['id']]);

    if (!$booking || !in_array($booking['status'], ['pending', 'approved'], true)) {
        flash('Only active bookings can be cancelled.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    db_run(
        'UPDATE bookings SET status = "cancelled", agent_note = ? WHERE id = ?',
        ['Cancelled by user.', $bookingId]
    );
    NotificationService::notifyBookingStatus($bookingId, 'cancelled');

    flash('Booking cancelled successfully.', 'success');
    go('../dashboard.php?section=bookings');
}

if ($action === 'decide') {
    require_role(['company', 'agent']);
    $me = current_user();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['agent_note'] ?? '');

    if (!in_array($status, ['approved', 'rejected'], true)) {
        flash('Wrong booking status.', 'danger');
        go('../dashboard.php');
    }

    $companyId = managed_company_id($me);
    $booking = db_one('SELECT * FROM bookings WHERE id = ? AND company_id = ?', [$bookingId, $companyId]);

    if (!$booking) {
        flash('Booking not found for your company.', 'danger');
        go('../dashboard.php');
    }

    db_run(
        'UPDATE bookings SET status = ?, agent_id = ?, agent_note = ? WHERE id = ?',
        [$status, $me['id'], $note, $bookingId]
    );
    NotificationService::notifyBookingStatus($bookingId, $status === 'approved' ? 'confirmed' : $status);

    flash('Booking ' . $status . '.', 'success');
    go('../dashboard.php');
}

if ($action === 'admin_update') {
    require_role(['admin', 'super_admin']);
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
    $start = trim((string) ($_POST['start_date'] ?? ''));
    $end = trim((string) ($_POST['end_date'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? ''));
    $paymentStatus = trim((string) ($_POST['payment_status'] ?? ''));
    $withDriver = isset($_POST['with_driver']) ? 1 : 0;
    $note = trim((string) ($_POST['agent_note'] ?? ''));

    if ($bookingId < 1 || $vehicleId < 1 || $start === '' || $end === '' || strtotime($end) < strtotime($start)) {
        flash('Admin booking update data is not valid.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if (!in_array($status, ['pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled'], true)) {
        flash('Booking status is not valid.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if (!in_array($paymentStatus, ['cash_due', 'pending', 'paid', 'failed', 'refunded'], true)) {
        flash('Payment status is not valid.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    $booking = db_one('SELECT * FROM bookings WHERE id = ?', [$bookingId]);
    $vehicle = db_one('SELECT * FROM vehicles WHERE id = ?', [$vehicleId]);

    if (!$booking || !$vehicle) {
        flash('Booking or vehicle was not found.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    if (vehicle_unavailable_reason($vehicleId, $start, $end, $bookingId) !== '') {
        flash('Selected vehicle is unavailable for the updated dates.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    $dailyRate = $withDriver ? (float) $vehicle['with_driver_price'] : (float) $vehicle['self_drive_price'];
    $total = booking_days($start, $end) * $dailyRate;

    db_run(
        'UPDATE bookings
         SET vehicle_id = ?, company_id = ?, start_datetime = ?, end_datetime = ?, start_date = ?, end_date = ?,
             with_driver = ?, daily_rate = ?, total_price = ?, status = ?, payment_status = ?, agent_note = ?
         WHERE id = ?',
        [
            $vehicleId,
            (int) $vehicle['company_id'],
            booking_start_datetime($start),
            booking_end_datetime($end),
            $start,
            $end,
            $withDriver,
            $dailyRate,
            $total,
            $status,
            $paymentStatus,
            $note,
            $bookingId,
        ]
    );
    NotificationService::notifyBookingStatus($bookingId, $status === 'approved' ? 'confirmed' : $status);
    if ($status === 'completed') {
        NotificationService::notifyBookingCompleted($bookingId);
    }

    flash('Booking updated by admin.', 'success');
    go('../dashboard.php?section=bookings');
}

if ($action === 'admin_cancel') {
    require_role(['admin', 'super_admin']);
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    if ($bookingId < 1) {
        flash('Booking id is required.', 'danger');
        go('../dashboard.php?section=bookings');
    }

    db_run('UPDATE bookings SET status = "cancelled", agent_note = ? WHERE id = ?', ['Cancelled by platform admin.', $bookingId]);
    NotificationService::notifyBookingStatus($bookingId, 'cancelled');
    flash('Booking cancelled by admin.', 'success');
    go('../dashboard.php?section=bookings');
}

go('../dashboard.php');
