<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role(['company', 'agent']);
require_csrf();

$companyId = managed_company_id();
$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

if (!$companyId || $vehicleId < 1) {
    set_flash('Invalid vehicle delete request.', 'danger');
    redirect('dashboard.php?section=vehicles');
}

$vehicle = db_one('SELECT * FROM vehicles WHERE id = ? AND company_id = ?', [$vehicleId, $companyId]);

if (!$vehicle) {
    set_flash('Vehicle not found.', 'danger');
    redirect('dashboard.php?section=vehicles');
}

$bookingCount = (int) db_value('SELECT COUNT(*) FROM bookings WHERE vehicle_id = ?', [$vehicleId]);

if ($bookingCount > 0) {
    set_flash('This vehicle already has booking history. Mark it unavailable instead of deleting it.', 'warning');
    redirect('dashboard.php?section=vehicles');
}

$images = db_all('SELECT file_name FROM vehicle_images WHERE vehicle_id = ?', [$vehicleId]);
$pdo = require_db();

try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM vehicles WHERE id = ? AND company_id = ?')->execute([$vehicleId, $companyId]);
    $pdo->commit();

    foreach ($images as $image) {
        if (!empty($image['file_name'])) {
            delete_uploaded_file(VEHICLE_UPLOAD_DIR, $image['file_name']);
        }
    }

    set_flash('Vehicle deleted successfully.', 'success');
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('Unable to delete vehicle: ' . $throwable->getMessage(), 'danger');
}

redirect('dashboard.php?section=vehicles');


