<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/backend/models/NotificationService.php';

header('Content-Type: application/json');

function json_out($ok, $message, $data = [], $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $ok,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function input_data()
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : $_POST;
}

function api_user_required($roles = [])
{
    $user = api_token_user();
    if (!$user) {
        json_out(false, 'JWT token required.', [], 401);
    }

    if ($roles && !role_allowed($user['role_name'], $roles)) {
        json_out(false, 'Not allowed for this role.', [], 403);
    }

    return $user;
}

function api_company_scope_or_forbidden($user, $companyId)
{
    $allowedCompanyId = require_company_resource_access($user, (int) $companyId);

    if ($allowedCompanyId < 1 && !is_platform_admin_role($user['role_name'])) {
        json_out(false, 'You cannot access resources outside your company.', [], 403);
    }

    return $allowedCompanyId;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'vehicles';
$data = input_data();

if ($action === 'login') {
    $login = trim($data['login'] ?? $data['email'] ?? $data['phone'] ?? '');
    $password = $data['password'] ?? '';

    $user = db_one(
        'SELECT u.*, r.name AS role_name
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.email = ? OR u.phone = ?
         LIMIT 1',
        [$login, $login]
    );

    if (!$user || !password_verify($password, $user['password']) || !$user['is_verified'] || $user['status'] !== 'active') {
        json_out(false, 'Invalid login or unverified account.', [], 401);
    }

    $refresh = make_refresh_token($user['id']);
    json_out(true, 'API login success.', [
        'jwt' => make_jwt($user),
        'refresh_token' => $refresh['token'],
        'refresh_expires_at' => $refresh['expires_at'],
        'role' => $user['role_name']
    ]);
}

if ($action === 'refresh') {
    $token = trim($data['refresh_token'] ?? '');
    $row = db_one(
        'SELECT * FROM refresh_tokens WHERE token = ? AND is_revoked = 0 AND expires_at >= NOW()',
        [$token]
    );

    if (!$row) {
        json_out(false, 'Refresh token invalid.', [], 401);
    }

    $user = find_user($row['user_id']);
    json_out(true, 'New JWT generated.', ['jwt' => make_jwt($user)]);
}

if ($action === 'logout') {
    $token = trim($data['refresh_token'] ?? '');
    if ($token !== '') {
        db_run('UPDATE refresh_tokens SET is_revoked = 1 WHERE token = ?', [$token]);
    }
    json_out(true, 'API logout done.');
}

if ($action === 'register') {
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';

    if ($name === '' || $email === '' || $phone === '' || strlen($password) < 6) {
        json_out(false, 'Name, email, phone and password are required.', [], 422);
    }

    if (db_one('SELECT id FROM users WHERE email = ? OR phone = ?', [$email, $phone])) {
        json_out(false, 'Email or phone already exists.', [], 409);
    }

    db_run(
        'INSERT INTO users (role_id, name, email, phone, password)
         VALUES (?, ?, ?, ?, ?)',
        [role_id('user'), $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]
    );

    $id = (int) db()->lastInsertId();
    $otp = create_otp($id, 'verify');
    send_otp_email($email, $name, $otp['code']);
    json_out(true, 'Registered. Verify OTP before login.', [
        'user_id' => $id
    ]);
}

if ($action === 'vehicles') {
    $search = trim($_GET['search'] ?? '');
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $categoryId = (int) ($_GET['category_id'] ?? 0);
    $typeId = (int) ($_GET['type_id'] ?? 0);
    $minPrice = (float) ($_GET['min_price'] ?? 0);
    $maxPrice = (float) ($_GET['max_price'] ?? 0);

    $params = [];
    $where = 'WHERE v.status = "available"';

    if ($startDate !== '' && $endDate !== '' && strtotime($endDate) >= strtotime($startDate)) {
        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM maintenance m
            WHERE m.vehicle_id = v.id AND ? <= m.end_date AND ? >= m.start_date
        )';
        $params[] = $startDate;
        $params[] = $endDate;

        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM bookings b
            WHERE b.vehicle_id = v.id AND b.status IN ("pending", "approved", "confirmed")
            AND ? <= b.end_date AND ? >= b.start_date
        )';
        $params[] = $startDate;
        $params[] = $endDate;

        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM availability_blocks ab
            WHERE ab.vehicle_id = v.id AND ? <= DATE(ab.end_datetime) AND ? >= DATE(ab.start_datetime)
        )';
        $params[] = $startDate;
        $params[] = $endDate;
    } else {
        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM maintenance m
            WHERE m.vehicle_id = v.id AND CURDATE() BETWEEN m.start_date AND m.end_date
        )';

        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM bookings b
            WHERE b.vehicle_id = v.id AND b.status IN ("pending", "approved", "confirmed")
            AND CURDATE() BETWEEN b.start_date AND b.end_date
        )';

        $where .= ' AND NOT EXISTS (
            SELECT 1 FROM availability_blocks ab
            WHERE ab.vehicle_id = v.id AND CURDATE() BETWEEN DATE(ab.start_datetime) AND DATE(ab.end_datetime)
        )';
    }

    if ($search !== '') {
        $where .= ' AND (v.name LIKE ? OR COALESCE(NULLIF(u.company_name, ""), u.name) LIKE ? OR v.location LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    if ($categoryId > 0) {
        $where .= ' AND v.category_id = ?';
        $params[] = $categoryId;
    }

    if ($typeId > 0) {
        $where .= ' AND v.type_id = ?';
        $params[] = $typeId;
    }

    if ($minPrice > 0) {
        $where .= ' AND v.self_drive_price >= ?';
        $params[] = $minPrice;
    }

    if ($maxPrice > 0) {
        $where .= ' AND v.self_drive_price <= ?';
        $params[] = $maxPrice;
    }

    $vehicles = db_all(
        'SELECT v.id, v.company_id, v.category_id, v.type_id, v.name, v.location,
                v.self_drive_price, v.with_driver_price, v.description, v.status,
                v.latitude, v.longitude, v.image, v.created_at,
                COALESCE(NULLIF(u.company_name, ""), u.name) AS company_name, c.name AS category_name, t.name AS type_name,
                (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE vehicle_id = v.id AND status = "published") AS average_rating,
                (SELECT COUNT(*) FROM reviews WHERE vehicle_id = v.id AND status = "published") AS review_count
         FROM vehicles v JOIN users u ON u.id = v.company_id
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         ' . $where . '
         ORDER BY v.id DESC',
        $params
    );

    json_out(true, 'Vehicles loaded.', ['vehicles' => $vehicles]);
}

if ($action === 'vehicle_save') {
    $user = api_user_required(['company', 'agent', 'admin', 'super_admin']);
    $id = (int) ($data['vehicle_id'] ?? 0);
    $companyId = managed_company_id($user);
    $categoryId = (int) ($data['category_id'] ?? 0);
    $typeId = (int) ($data['type_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $location = trim($data['location'] ?? '');
    $selfPrice = (float) ($data['self_drive_price'] ?? 0);
    $driverPrice = (float) ($data['with_driver_price'] ?? 0);

    if (is_platform_admin_role($user['role_name'])) {
        $companyId = (int) ($data['company_id'] ?? 0);
    }

    if ($companyId < 1) {
        json_out(false, 'Company scope is required.', [], 403);
    }

    api_company_scope_or_forbidden($user, $companyId);

    if (!$categoryId || !$typeId || $name === '' || $location === '' || $selfPrice <= 0 || $driverPrice <= 0) {
        json_out(false, 'Vehicle fields missing.', [], 422);
    }

    $validType = db_one(
        'SELECT id FROM vehicle_types WHERE id = ? AND category_id = ?',
        [$typeId, $categoryId]
    );

    if (!$validType) {
        json_out(false, 'Vehicle type does not match category.', [], 422);
    }

    if ($id > 0) {
        db_run(
            'UPDATE vehicles SET category_id = ?, type_id = ?, name = ?, location = ?, self_drive_price = ?, with_driver_price = ?
             WHERE id = ? AND company_id = ?',
            [$categoryId, $typeId, $name, $location, $selfPrice, $driverPrice, $id, $companyId]
        );
        json_out(true, 'Vehicle updated.', ['vehicle_id' => $id]);
    }

    db_run(
        'INSERT INTO vehicles (company_id, category_id, type_id, name, location, self_drive_price, with_driver_price)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$companyId, $categoryId, $typeId, $name, $location, $selfPrice, $driverPrice]
    );
    json_out(true, 'Vehicle created.', ['vehicle_id' => db()->lastInsertId()]);
}

if ($action === 'vehicle_delete') {
    $user = api_user_required(['company', 'agent', 'admin', 'super_admin']);
    $id = (int) ($data['vehicle_id'] ?? 0);
    $vehicle = db_one('SELECT * FROM vehicles WHERE id = ? LIMIT 1', [$id]);

    if (!$vehicle) {
        json_out(false, 'Vehicle not found.', [], 404);
    }

    api_company_scope_or_forbidden($user, (int) $vehicle['company_id']);
    db_run('DELETE FROM vehicles WHERE id = ? AND company_id = ?', [$id, $vehicle['company_id']]);
    json_out(true, 'Vehicle deleted.');
}

if ($action === 'booking_decide') {
    $user = api_user_required(['company', 'agent', 'admin', 'super_admin']);
    $bookingId = (int) ($data['booking_id'] ?? 0);
    $status = $data['status'] ?? '';

    if (!in_array($status, ['approved', 'rejected'], true)) {
        json_out(false, 'Use approved or rejected.', [], 422);
    }

    $booking = db_one('SELECT * FROM bookings WHERE id = ? LIMIT 1', [$bookingId]);

    if (!$booking) {
        json_out(false, 'Booking not found.', [], 404);
    }

    api_company_scope_or_forbidden($user, (int) $booking['company_id']);
    db_run('UPDATE bookings SET status = ?, agent_id = ? WHERE id = ?', [$status, $user['id'], $bookingId]);

    json_out(true, 'Booking updated.');
}

if ($action === 'admin_bookings') {
    $user = api_user_required(['admin', 'super_admin', 'company', 'agent']);
    $status = trim((string) ($_GET['status'] ?? ''));
    $search = trim((string) ($_GET['search'] ?? ''));
    $startDate = trim((string) ($_GET['start_date'] ?? ''));
    $endDate = trim((string) ($_GET['end_date'] ?? ''));
    $userId = (int) ($_GET['user_id'] ?? 0);

    $where = [];
    $params = [];

    if (!is_platform_admin_role($user['role_name'])) {
        $companyId = managed_company_id($user);
        if ($companyId < 1) {
            json_out(false, 'Company scope is required.', [], 403);
        }
        $where[] = 'b.company_id = ?';
        $params[] = $companyId;
    }

    if ($status !== '' && in_array($status, ['pending', 'confirmed', 'approved', 'completed', 'rejected', 'cancelled'], true)) {
        $where[] = 'b.status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $where[] = '(v.name LIKE ? OR renter.name LIKE ? OR renter.email LIKE ? OR company.company_name LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }

    if ($userId > 0) {
        $where[] = 'b.user_id = ?';
        $params[] = $userId;
    }

    if ($startDate !== '') {
        $where[] = 'b.start_date >= ?';
        $params[] = $startDate;
    }

    if ($endDate !== '') {
        $where[] = 'b.end_date <= ?';
        $params[] = $endDate;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $bookings = db_all(
        'SELECT b.id, b.start_date, b.end_date, b.total_price, b.status, b.payment_status,
                v.name AS vehicle_name, renter.name AS user_name, renter.email AS user_email,
                company.company_name
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         JOIN users renter ON renter.id = b.user_id
         JOIN users company ON company.id = b.company_id
         ' . $whereSql . '
         ORDER BY b.created_at DESC, b.id DESC
         LIMIT 100',
        $params
    );

    json_out(true, 'Bookings loaded.', ['bookings' => $bookings]);
}

if ($action === 'revenue_summary') {
    $user = api_user_required(['admin', 'super_admin', 'company', 'agent']);
    $month = (int) ($_GET['month'] ?? date('n'));
    $year = (int) ($_GET['year'] ?? date('Y'));
    $month = $month >= 1 && $month <= 12 ? $month : (int) date('n');
    $year = $year >= 2020 && $year <= 2100 ? $year : (int) date('Y');
    $start = sprintf('%04d-%02d-01', $year, $month);
    $end = date('Y-m-t', strtotime($start));
    $where = 'WHERE b.payment_status = "paid" AND b.start_date BETWEEN ? AND ?';
    $params = [$start, $end];

    if (!is_platform_admin_role($user['role_name'])) {
        $companyId = managed_company_id($user);
        if ($companyId < 1) {
            json_out(false, 'Company scope is required.', [], 403);
        }
        $where .= ' AND b.company_id = ?';
        $params[] = $companyId;
    }

    $summary = db_one(
        'SELECT COALESCE(SUM(b.total_price), 0) AS revenue_total, COUNT(*) AS paid_bookings
         FROM bookings b ' . $where,
        $params
    );

    $series = db_all(
        'SELECT DATE_FORMAT(b.start_date, "%Y-%m-%d") AS day, COALESCE(SUM(b.total_price), 0) AS revenue_total
         FROM bookings b
         ' . $where . '
         GROUP BY DATE_FORMAT(b.start_date, "%Y-%m-%d")
         ORDER BY day',
        $params
    );

    json_out(true, 'Revenue summary loaded.', [
        'month' => $month,
        'year' => $year,
        'summary' => $summary,
        'series' => $series,
    ]);
}

if ($action === 'review_submit') {
    $user = api_user_required(['user']);
    $bookingId = (int) ($data['booking_id'] ?? 0);
    $rating = (int) ($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    $booking = db_one(
        'SELECT * FROM bookings WHERE id = ? AND user_id = ?',
        [$bookingId, $user['id']]
    );

    if (!$booking) {
        json_out(false, 'Booking not found.', [], 404);
    }

    if (!user_can_review_booking($booking)) {
        json_out(false, 'Review allowed only after a paid rental is completed.', [], 403);
    }

    if ($rating < 1 || $rating > 5) {
        json_out(false, 'Rating must be between 1 and 5.', [], 422);
    }

    if (db_one('SELECT id FROM reviews WHERE booking_id = ?', [$bookingId])) {
        json_out(false, 'This booking already has a review.', [], 409);
    }

    db_run(
        'INSERT INTO reviews (booking_id, vehicle_id, user_id, rating, comment)
         VALUES (?, ?, ?, ?, ?)',
        [$bookingId, $booking['vehicle_id'], $user['id'], $rating, $comment]
    );

    json_out(true, 'Review submitted.', [
        'review_id' => db()->lastInsertId(),
        'vehicle_id' => (int) $booking['vehicle_id'],
    ]);
}

if ($action === 'site_rating_submit') {
    $user = api_user_required(['user']);
    $rating = (int) ($data['rating'] ?? 0);
    $feedback = trim((string) ($data['feedback'] ?? ''));

    if ($rating < 1 || $rating > 5) {
        json_out(false, 'Rating must be between 1 and 5.', [], 422);
    }

    if (!user_can_rate_site((int) $user['id'])) {
        json_out(false, 'You have already rated the website.', [], 409);
    }

    db_run(
        'INSERT INTO site_reviews (user_id, rating, feedback) VALUES (?, ?, ?)',
        [(int) $user['id'], $rating, $feedback]
    );

    json_out(true, 'Website rating submitted.', ['rating_id' => db()->lastInsertId()]);
}

if ($action === 'notifications') {
    $user = api_user_required();
    json_out(true, 'Notifications loaded.', [
        'unread_count' => NotificationService::unreadCount((int) $user['id']),
        'notifications' => NotificationService::latestForUser((int) $user['id'], 20),
    ]);
}

if ($action === 'notification_mark_read') {
    $user = api_user_required();
    $notificationId = (int) ($data['notification_id'] ?? 0);

    if ($notificationId > 0) {
        NotificationService::markRead($notificationId, (int) $user['id']);
    } else {
        NotificationService::markAllRead((int) $user['id']);
    }

    json_out(true, 'Notification state updated.', [
        'unread_count' => NotificationService::unreadCount((int) $user['id']),
    ]);
}

json_out(false, 'Unknown API action.', [], 404);
