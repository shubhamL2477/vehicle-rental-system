<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role(['company', 'agent']);
require_csrf();

$companyId = managed_company_id();
$user = current_user();
$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
$startDatetime = (string) ($_POST['start_datetime'] ?? '');
$endDatetime = (string) ($_POST['end_datetime'] ?? '');
$reason = trim((string) ($_POST['reason'] ?? ''));

if (!$companyId || $vehicleId < 1 || booking_days($startDatetime, $endDatetime) < 1) {
    set_flash('Please enter a valid blackout range.', 'danger');
    redirect('dashboard.php?section=vehicles');
}

$vehicle = db_one('SELECT id FROM vehicles WHERE id = ? AND company_id = ?', [$vehicleId, $companyId]);

if (!$vehicle) {
    set_flash('Vehicle not found.', 'danger');
    redirect('dashboard.php?section=vehicles');
}

$pdo = require_db();
$pdo->prepare(
    'INSERT INTO availability_blocks (vehicle_id, company_id, start_datetime, end_datetime, reason, created_by_user_id)
     VALUES (?, ?, ?, ?, ?, ?)'
)->execute([
    $vehicleId,
    $companyId,
    $startDatetime,
    $endDatetime,
    $reason,
    (int) ($user['id'] ?? 0),
]);

set_flash('Availability block saved.', 'success');
redirect('dashboard.php?section=vehicles');


