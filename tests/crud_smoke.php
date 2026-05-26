<?php
/**
 * CLI smoke test for Sprint 1 CRUD verification.
 *
 * Run:
 * php tests/crud_smoke.php
 *
 * The test creates isolated smoke rows, verifies core CRUD paths, and removes
 * those rows even when the local database uses the older companies table shape.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../backend/models/BookingModel.php';
require_once __DIR__ . '/../backend/models/MaintenanceModel.php';

$stamp = date('YmdHis') . random_int(100, 999);
$checks = [];
$created = [
    'users' => [],
    'companies' => [],
    'categories' => [],
    'types' => [],
    'vehicles' => [],
    'bookings' => [],
    'maintenance_records' => [],
    'availability_blocks' => [],
    'service_history' => [],
];

function smoke_check($condition, $message, &$checks)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    $checks[] = $message;
}

function smoke_insert($table, $data)
{
    $columns = [];
    $values = [];

    foreach ($data as $column => $value) {
        if (db_column_exists($table, $column)) {
            $columns[] = $column;
            $values[] = $value;
        }
    }

    if (!$columns) {
        throw new RuntimeException('No matching columns for ' . $table . '.');
    }

    db_run(
        'INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
        $values
    );

    return (int) db()->lastInsertId();
}

function smoke_vehicle_payload($companyId, $categoryId, $typeId, $name, $location, $createdByUserId)
{
    return [
        'company_id' => $companyId,
        'category_id' => $categoryId,
        'type_id' => $typeId,
        'name' => $name,
        'type' => 'car',
        'description' => 'Smoke vehicle',
        'price_per_day' => 1000,
        'driver_price_per_day' => 1500,
        'seating_capacity' => 4,
        'location' => $location,
        'self_drive_price' => 1000,
        'with_driver_price' => 1500,
        'latitude' => 27.7172000,
        'longitude' => 85.3240000,
        'status' => 'available',
        'created_by_user_id' => $createdByUserId,
    ];
}

function smoke_cleanup($created)
{
    foreach ($created['service_history'] as $id) {
        db_run('DELETE FROM service_history WHERE id = ?', [$id]);
    }
    foreach ($created['maintenance_records'] as $id) {
        db_run('DELETE FROM maintenance_records WHERE id = ?', [$id]);
    }
    foreach ($created['availability_blocks'] as $id) {
        db_run('DELETE FROM availability_blocks WHERE id = ?', [$id]);
    }
    foreach ($created['vehicles'] as $id) {
        db_run('DELETE FROM maintenance WHERE vehicle_id = ?', [$id]);
        db_run('DELETE FROM bookings WHERE vehicle_id = ?', [$id]);
        db_run('DELETE FROM vehicles WHERE id = ?', [$id]);
    }
    foreach ($created['bookings'] as $id) {
        db_run('DELETE FROM bookings WHERE id = ?', [$id]);
    }
    foreach ($created['types'] as $id) {
        db_run('DELETE FROM vehicle_types WHERE id = ?', [$id]);
    }
    foreach ($created['categories'] as $id) {
        db_run('DELETE FROM vehicle_categories WHERE id = ?', [$id]);
    }
    foreach ($created['companies'] as $id) {
        db_run('DELETE FROM companies WHERE id = ?', [$id]);
    }
    foreach ($created['users'] as $id) {
        db_run('DELETE FROM otp_codes WHERE user_id = ?', [$id]);
        db_run('DELETE FROM users WHERE id = ?', [$id]);
    }
}

try {
    ensure_otp_schema();
    ensure_service_history_schema();

    $companyRoleId = role_id('company');
    $userRoleId = role_id('user');
    smoke_check($companyRoleId > 0 && $userRoleId > 0, 'roles can be read', $checks);

    $companyUserId = smoke_insert('users', [
        'role_id' => $companyRoleId,
        'company_id' => null,
        'name' => 'Smoke Company',
        'email' => 'smoke-company-' . $stamp . '@test.local',
        'phone' => '98' . $stamp,
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'company_name' => 'Smoke Rentals',
        'role' => 'company',
        'status' => 'active',
        'is_verified' => 1,
        'address' => 'Smoke Street',
    ]);
    $created['users'][] = $companyUserId;

    $vehicleCompanyId = $companyUserId;
    if (company_table_enabled()) {
        $vehicleCompanyId = smoke_insert('companies', [
            'owner_user_id' => $companyUserId,
            'name' => 'Smoke Rentals ' . $stamp,
            'description' => 'Smoke company for CRUD verification',
            'address' => 'Smoke Street',
            'contact_email' => 'smoke-company-' . $stamp . '@test.local',
            'contact_phone' => '98' . $stamp,
            'status' => 'approved',
        ]);
        $created['companies'][] = $vehicleCompanyId;
    }

    $userId = smoke_insert('users', [
        'role_id' => $userRoleId,
        'company_id' => null,
        'name' => 'Smoke User',
        'email' => 'smoke-user-' . $stamp . '@test.local',
        'phone' => '97' . $stamp,
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'user',
        'status' => 'active',
        'is_verified' => 1,
    ]);
    $created['users'][] = $userId;
    smoke_check((bool) find_user($userId), 'users can be created and read', $checks);

    $otp = create_otp($userId, 'verify');
    smoke_check(!empty($otp['code']) && verify_otp_code($userId, $otp['code'], 'verify'), 'OTP create and verify works', $checks);

    $categoryId = smoke_insert('vehicle_categories', ['name' => 'Smoke Category ' . $stamp]);
    $created['categories'][] = $categoryId;
    $typeId = smoke_insert('vehicle_types', ['category_id' => $categoryId, 'name' => 'Smoke Type ' . $stamp]);
    $created['types'][] = $typeId;

    $vehicleId = smoke_insert(
        'vehicles',
        smoke_vehicle_payload($vehicleCompanyId, $categoryId, $typeId, 'Smoke Vehicle ' . $stamp, 'Kathmandu', $companyUserId)
    );
    $created['vehicles'][] = $vehicleId;

    db_run('UPDATE vehicles SET location = ? WHERE id = ?', ['Lalitpur', $vehicleId]);
    smoke_check(db_value('SELECT location FROM vehicles WHERE id = ?', [$vehicleId]) === 'Lalitpur', 'vehicles can be created, read, and updated', $checks);

    $deleteVehicleId = smoke_insert(
        'vehicles',
        smoke_vehicle_payload($vehicleCompanyId, $categoryId, $typeId, 'Smoke Delete Vehicle ' . $stamp, 'Bhaktapur', $companyUserId)
    );
    $created['vehicles'][] = $deleteVehicleId;
    db_run('DELETE FROM vehicles WHERE id = ?', [$deleteVehicleId]);
    smoke_check(!db_one('SELECT id FROM vehicles WHERE id = ?', [$deleteVehicleId]), 'vehicles can be deleted', $checks);

    $booking = BookingModel::createForUser(find_user($userId), [
        'vehicle_id' => $vehicleId,
        'start_date' => '2031-01-10',
        'end_date' => '2031-01-12',
        'pickup_location' => 'Kathmandu',
        'destination' => 'Pokhara',
        'payment_method' => 'cash',
        'terms_accepted' => 1,
    ]);
    $created['bookings'][] = (int) $booking['id'];
    smoke_check(!empty($booking['id']) && (float) $booking['total_price'] > 0, 'bookings can be created and priced', $checks);

    $maintenance = MaintenanceModel::save(find_user($companyUserId), [
        'vehicle_id' => $vehicleId,
        'title' => 'Smoke Maintenance',
        'start_date' => '2031-02-01',
        'end_date' => '2031-02-02',
        'status' => 'scheduled',
    ]);
    $created['maintenance_records'][] = (int) $maintenance['id'];
    if (!empty($maintenance['availability_block_id'])) {
        $created['availability_blocks'][] = (int) $maintenance['availability_block_id'];
    }
    smoke_check(!empty($maintenance['availability_block_id']), 'maintenance records create availability blocks', $checks);

    $serviceId = smoke_insert('service_history', [
        'vehicle_id' => $vehicleId,
        'company_id' => $vehicleCompanyId,
        'service_type' => 'Inspection',
        'provider' => 'Smoke Provider',
        'mileage' => 100,
        'cost' => 50,
        'service_date' => '2031-02-03',
        'notes' => 'Smoke notes',
        'created_by_user_id' => $companyUserId,
    ]);
    $created['service_history'][] = $serviceId;
    smoke_check((int) db_value('SELECT COUNT(*) FROM service_history WHERE vehicle_id = ?', [$vehicleId]) === 1, 'service history can be created and read', $checks);

    smoke_cleanup($created);

    echo "CRUD smoke test passed:\n";
    foreach ($checks as $check) {
        echo '- ' . $check . "\n";
    }
} catch (Throwable $throwable) {
    try {
        smoke_cleanup($created);
    } catch (Throwable $cleanupThrowable) {
        fwrite(STDERR, 'Cleanup failed: ' . $cleanupThrowable->getMessage() . PHP_EOL);
    }

    fwrite(STDERR, 'CRUD smoke test failed: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
