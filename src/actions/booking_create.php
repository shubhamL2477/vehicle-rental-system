<?php

require_once __DIR__ . '/../includes/bootstrap.php';

// Only normal user accounts can create bookings.
require_role('user');
require_csrf();

$viewer = current_user();
$vehicleId = isset($_POST['vehicle_id']) ? (int) $_POST['vehicle_id'] : 0;
$startDatetime = isset($_POST['start_datetime']) ? $_POST['start_datetime'] : '';
$endDatetime = isset($_POST['end_datetime']) ? $_POST['end_datetime'] : '';
$pickupLocation = isset($_POST['pickup_location']) ? trim($_POST['pickup_location']) : '';
$destination = isset($_POST['destination']) ? trim($_POST['destination']) : '';
$identityType = isset($_POST['identity_type']) ? $_POST['identity_type'] : 'passport';
$withDriver = isset($_POST['with_driver']) ? bool_from_input($_POST['with_driver']) : false;
$termsAccepted = isset($_POST['terms_accepted']) ? bool_from_input($_POST['terms_accepted']) : false;

if ($vehicleId < 1 || $pickupLocation === '' || $destination === '' || booking_days($startDatetime, $endDatetime) < 1) {
    set_flash('Please complete the booking form with valid dates and locations.', 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}

if (!in_array($identityType, ['passport', 'citizenship'], true)) {
    set_flash('Please choose a valid identity document type.', 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}

if (!$termsAccepted) {
    set_flash('You must accept the booking terms before submitting.', 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}

$vehicle = db_one('SELECT * FROM vehicles WHERE id = ?', [$vehicleId]);
$company = null;

if ($vehicle) {
    $company = db_one('SELECT * FROM companies WHERE id = ?', [$vehicle['company_id']]);
}

if (!$vehicle || !$company || $company['status'] !== 'approved') {
    set_flash('The selected vehicle is not available for booking.', 'danger');
    redirect('vehicles.php');
}

if ($vehicle['status'] !== 'available') {
    set_flash('This vehicle is currently unavailable.', 'warning');
    redirect('vehicle.php?id=' . $vehicleId);
}

$overlapBooking = db_one(
    "SELECT id FROM bookings
     WHERE vehicle_id = ?
       AND status IN ('pending', 'confirmed')
       AND ? < end_datetime
       AND ? > start_datetime
     LIMIT 1",
    [$vehicleId, $startDatetime, $endDatetime]
);

if ($overlapBooking) {
    set_flash('That vehicle already has a booking in the selected period.', 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}

$blocked = db_one(
    'SELECT id FROM availability_blocks
     WHERE vehicle_id = ?
       AND ? < end_datetime
       AND ? > start_datetime
     LIMIT 1',
    [$vehicleId, $startDatetime, $endDatetime]
);

if ($blocked) {
    set_flash('That vehicle is blocked for maintenance or blackout during those dates.', 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}

if (empty($_FILES['identity_document']['name'])) {
    set_flash('Citizenship or passport document is required.', 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}

if (!$withDriver && empty($_FILES['license_document']['name'])) {
    set_flash('License is required when booking without driver.', 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}

$totalPrice = calculate_booking_total($vehicle, $startDatetime, $endDatetime, $withDriver);

if ($totalPrice <= 0) {
    set_flash('Unable to calculate the booking total.', 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}

$pdo = require_db();

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        "INSERT INTO bookings
         (user_id, vehicle_id, company_id, start_datetime, end_datetime, pickup_location, destination, with_driver, total_price, status, terms_accepted, payment_method, payment_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'cash', 'cash_due')"
    )->execute([
        (int) ($viewer['id'] ?? 0),
        $vehicleId,
        (int) $company['id'],
        $startDatetime,
        $endDatetime,
        $pickupLocation,
        $destination,
        $withDriver ? 1 : 0,
        $totalPrice,
        $termsAccepted ? 1 : 0,
    ]);

    $bookingId = (int) $pdo->lastInsertId();

    if (!$withDriver && !empty($_FILES['license_document']['name'])) {
        $licenseFiles = save_uploaded_files($_FILES['license_document'], DOCUMENT_UPLOAD_DIR, ALLOWED_DOCUMENT_EXTENSIONS);

        foreach ($licenseFiles as $file) {
            $pdo->prepare(
                'INSERT INTO booking_documents (booking_id, document_type, file_name, original_name)
                 VALUES (?, ?, ?, ?)'
            )->execute([$bookingId, 'license', $file['generated_name'], $file['original_name']]);
        }
    }

    $identityFiles = save_uploaded_files($_FILES['identity_document'], DOCUMENT_UPLOAD_DIR, ALLOWED_DOCUMENT_EXTENSIONS);

    foreach ($identityFiles as $file) {
        $pdo->prepare(
            'INSERT INTO booking_documents (booking_id, document_type, file_name, original_name)
             VALUES (?, ?, ?, ?)'
        )->execute([$bookingId, $identityType, $file['generated_name'], $file['original_name']]);
    }

    $pdo->commit();
    set_flash('Booking request submitted. The company or agent will review it shortly.', 'success');
    redirect('dashboard.php?section=bookings');
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('Unable to create booking: ' . $throwable->getMessage(), 'danger');
    redirect('vehicle.php?id=' . $vehicleId);
}


