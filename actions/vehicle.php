<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../backend/models/MaintenanceModel.php';

check_csrf();
require_role(['company', 'agent', 'admin', 'super_admin']);

$me = current_user();
$companyId = managed_company_id($me);
$action = $_POST['action'] ?? '';

if (!$companyId && !is_platform_admin_role($me['role_name'])) {
    flash('Agent is not linked to a company.', 'danger');
    go('../dashboard.php');
}

if ($action === 'save') {
    $id = (int) ($_POST['vehicle_id'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $typeId = (int) ($_POST['type_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $selfPrice = (float) ($_POST['self_drive_price'] ?? 0);
    $driverPrice = (float) ($_POST['with_driver_price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'available';
    $lat = trim($_POST['latitude'] ?? '');
    $lng = trim($_POST['longitude'] ?? '');

    if (!$companyId || !$categoryId || !$typeId || $name === '' || $location === '' || $selfPrice <= 0 || $driverPrice <= 0) {
        flash('Vehicle category, type, name, location and both prices are required.', 'danger');
        go('../dashboard.php');
    }

    $validType = db_one(
        'SELECT id FROM vehicle_types WHERE id = ? AND category_id = ?',
        [$typeId, $categoryId]
    );

    if (!$validType) {
        flash('Selected vehicle type does not match the category.', 'danger');
        go('../dashboard.php');
    }

    if (!in_array($status, ['available', 'unavailable'], true)) {
        $status = 'available';
    }

    $image = save_upload('image', VEHICLE_UPLOAD_PATH, 'uploads/vehicles/');

    if ($id > 0) {
        $old = db_one('SELECT * FROM vehicles WHERE id = ? AND company_id = ?', [$id, $companyId]);
        if (!$old) {
            flash('Vehicle not found.', 'danger');
            go('../dashboard.php');
        }

        if ($image === '') {
            $image = $old['image'];
        }

        db_run(
            'UPDATE vehicles
             SET category_id = ?, type_id = ?, name = ?, location = ?, self_drive_price = ?, with_driver_price = ?,
                 description = ?, status = ?, latitude = ?, longitude = ?, image = ?
             WHERE id = ? AND company_id = ?',
            [$categoryId, $typeId, $name, $location, $selfPrice, $driverPrice, $description, $status, $lat ?: null, $lng ?: null, $image, $id, $companyId]
        );

        flash('Vehicle updated.', 'success');
        go('../dashboard.php');
    }

    db_run(
        'INSERT INTO vehicles
         (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price, description, status, latitude, longitude, image)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$companyId, $categoryId, $typeId, $name, $location, $selfPrice, $driverPrice, $description, $status, $lat ?: null, $lng ?: null, $image ?: null]
    );

    flash('Vehicle added.', 'success');
    go('../dashboard.php');
}

if ($action === 'delete') {
    $id = (int) ($_POST['vehicle_id'] ?? 0);

    $hasBooking = db_value('SELECT COUNT(*) FROM bookings WHERE vehicle_id = ?', [$id]);
    if ($hasBooking > 0) {
        flash('Vehicle has booking history. Mark it unavailable instead.', 'warning');
        go('../dashboard.php');
    }

    db_run('DELETE FROM vehicles WHERE id = ? AND company_id = ?', [$id, $companyId]);
    flash('Vehicle deleted.', 'success');
    go('../dashboard.php');
}

if ($action === 'maintenance') {
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
    $recordId = (int) ($_POST['maintenance_id'] ?? 0);
    $vehicle = db_one('SELECT * FROM vehicles WHERE id = ? LIMIT 1', [$vehicleId]);

    if (!$vehicle || (!is_platform_admin_role($me['role_name']) && (int) $vehicle['company_id'] !== $companyId)) {
        flash('Vehicle not found for your company.', 'danger');
        go('../dashboard.php');
    }

    try {
        MaintenanceModel::save($me, [
            'maintenance_id' => $recordId,
            'vehicle_id' => $vehicleId,
            'title' => $_POST['title'] ?? $_POST['reason'] ?? '',
            'description' => $_POST['description'] ?? $_POST['reason'] ?? '',
            'cost' => $_POST['cost'] ?? 0,
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'status' => $_POST['status'] ?? 'scheduled',
        ]);
    } catch (Throwable $throwable) {
        flash('Maintenance could not be saved: ' . $throwable->getMessage(), 'danger');
        go('../dashboard.php');
    }

    flash($recordId > 0 ? 'Maintenance record updated.' : 'Maintenance record added.', 'success');
    go('../dashboard.php?section=maintenance');
}

if ($action === 'maintenance_delete') {
    $maintenanceId = (int) ($_POST['maintenance_id'] ?? 0);

    try {
        MaintenanceModel::delete($me, $maintenanceId);
    } catch (Throwable $throwable) {
        flash('Maintenance could not be deleted: ' . $throwable->getMessage(), 'danger');
        go('../dashboard.php?section=maintenance');
    }

    flash('Maintenance record deleted.', 'success');
    go('../dashboard.php?section=maintenance');
}

if ($action === 'service_history') {
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
    $vehicle = db_one('SELECT * FROM vehicles WHERE id = ? LIMIT 1', [$vehicleId]);

    if (!$vehicle || (!is_platform_admin_role($me['role_name']) && (int) $vehicle['company_id'] !== $companyId)) {
        flash('Vehicle not found for your company.', 'danger');
        go('../dashboard.php?section=maintenance');
    }

    $serviceType = trim((string) ($_POST['service_type'] ?? ''));
    $provider = trim((string) ($_POST['provider'] ?? ''));
    $mileage = (int) ($_POST['mileage'] ?? 0);
    $cost = (float) ($_POST['cost'] ?? 0);
    $serviceDate = trim((string) ($_POST['service_date'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($serviceType === '' || $serviceDate === '' || strtotime($serviceDate) === false) {
        flash('Service type and date are required.', 'danger');
        go('../dashboard.php?section=maintenance');
    }

    db_run(
        'INSERT INTO vehicle_service_history (vehicle_id, company_id, service_type, provider, mileage, cost, service_date, notes, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$vehicleId, (int) $vehicle['company_id'], $serviceType, $provider, max(0, $mileage), max(0, $cost), $serviceDate, $notes, (int) $me['id']]
    );

    flash('Service history added.', 'success');
    go('../dashboard.php?section=maintenance');
}

go('../dashboard.php');
