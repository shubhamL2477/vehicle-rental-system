<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/email.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function go($path)
{
    header('Location: ' . $path);
    exit;
}

function absolute_url($path = '')
{
    if (preg_match('#^https?://#', (string) $path)) {
        return (string) $path;
    }

    return APP_PUBLIC_URL . '/' . ltrim((string) $path, '/');
}

function flash($message, $type = 'info')
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash()
{
    $msg = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $msg;
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf()
{
    $sent = $_POST['csrf'] ?? '';
    if (!$sent || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        exit('Invalid CSRF token');
    }
}

function role_id($role)
{
    return (int) db_value('SELECT id FROM roles WHERE name = ?', [$role]);
}

function find_user($id)
{
    return db_one(
        'SELECT u.*, r.name AS role_name
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.id = ?',
        [$id]
    );
}

function current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return find_user((int) $_SESSION['user_id']);
}

function require_login()
{
    if (!current_user()) {
        flash('Please login first.', 'warning');
        go('login.php');
    }
}

function require_role($role)
{
    require_login();
    $user = current_user();
    $roles = is_array($role) ? $role : [$role];
    if (!role_allowed($user['role_name'], $roles)) {
        flash('You cannot open that page.', 'danger');
        go('dashboard.php');
    }
}

function is_platform_admin_role($role)
{
    return in_array($role, ['admin', 'super_admin'], true);
}

function role_allowed($role, $allowedRoles)
{
    if (in_array($role, $allowedRoles, true)) {
        return true;
    }

    return is_platform_admin_role($role) && (in_array('admin', $allowedRoles, true) || in_array('super_admin', $allowedRoles, true));
}

function managed_company_id($user)
{
    if (!$user) {
        return 0;
    }

    if ($user['role_name'] === 'company') {
        return (int) $user['id'];
    }

    if ($user['role_name'] === 'agent') {
        return (int) ($user['company_id'] ?? 0);
    }

    return 0;
}

function require_company_resource_access($user, $companyId)
{
    $companyId = (int) $companyId;

    if (is_platform_admin_role($user['role_name'])) {
        return $companyId;
    }

    if (managed_company_id($user) !== $companyId) {
        return 0;
    }

    return $companyId;
}

function role_badge($status)
{
    if ($status === 'approved' || $status === 'available' || $status === 'active') {
        return 'badge good';
    }
    if ($status === 'pending' || $status === 'pending_admin') {
        return 'badge wait';
    }
    return 'badge bad';
}

function money($amount)
{
    return 'Rs. ' . number_format((float) $amount, 2);
}

function booking_days($startDate, $endDate)
{
    $start = strtotime($startDate);
    $end = strtotime($endDate);

    if ($start === false || $end === false || $end < $start) {
        return 0;
    }

    return max(1, (int) floor(($end - $start) / 86400) + 1);
}

function booking_total($vehicle, $startDate, $endDate, $withDriver)
{
    $days = booking_days($startDate, $endDate);
    $rate = $withDriver ? (float) $vehicle['with_driver_price'] : (float) $vehicle['self_drive_price'];

    return $days * $rate;
}

function booking_start_datetime($startDate)
{
    return date('Y-m-d 00:00:00', strtotime((string) $startDate));
}

function booking_end_datetime($endDate)
{
    return date('Y-m-d 23:59:59', strtotime((string) $endDate));
}

function iso_datetime($datetime)
{
    $timestamp = strtotime((string) $datetime);

    if ($timestamp === false) {
        return '';
    }

    return date(DATE_ATOM, $timestamp);
}

function vehicle_unavailable_reason($vehicleId, $startDate, $endDate, $excludeBookingId = 0)
{
    $booking = db_one(
        'SELECT id FROM bookings
         WHERE vehicle_id = ?
           AND id <> ?
           AND status IN ("pending", "approved", "confirmed")
           AND ? <= end_date
           AND ? >= start_date
         LIMIT 1',
        [(int) $vehicleId, (int) $excludeBookingId, $startDate, $endDate]
    );

    if ($booking) {
        return 'booking';
    }

    $maintenance = db_one(
        'SELECT id FROM maintenance
         WHERE vehicle_id = ?
           AND ? <= end_date
           AND ? >= start_date
         LIMIT 1',
        [(int) $vehicleId, $startDate, $endDate]
    );

    if ($maintenance) {
        return 'maintenance';
    }

    $block = db_one(
        'SELECT id FROM availability_blocks
         WHERE vehicle_id = ?
           AND ? <= DATE(end_datetime)
           AND ? >= DATE(start_datetime)
         LIMIT 1',
        [(int) $vehicleId, $startDate, $endDate]
    );

    if ($block) {
        return 'availability_block';
    }

    return '';
}

function available_vehicle_options($excludeVehicleId = 0)
{
    return db_all(
        'SELECT v.id, v.name, v.location, v.self_drive_price, v.with_driver_price,
                u.company_name, c.name AS category_name, t.name AS type_name
         FROM vehicles v
         JOIN users u ON u.id = v.company_id
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         WHERE v.status = "available" AND v.id <> ?
         ORDER BY c.name, v.name',
        [(int) $excludeVehicleId]
    );
}

function payment_for_booking($bookingId)
{
    return db_one(
        'SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1',
        [(int) $bookingId]
    );
}

function payment_badge($status)
{
    if ($status === 'paid') {
        return 'badge good';
    }

    if ($status === 'pending' || $status === 'cash_due') {
        return 'badge wait';
    }

    return 'badge bad';
}

function make_transaction_id()
{
    return 'HYR-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function payment_webhook_signature($transactionId, $amount, $status)
{
    return hash_hmac('sha256', $transactionId . '|' . number_format((float) $amount, 2, '.', '') . '|' . $status, JWT_SECRET);
}

function vehicle_review_stats($vehicleId)
{
    $stats = db_one(
        'SELECT ROUND(AVG(rating), 1) AS average_rating, COUNT(*) AS review_count
         FROM reviews
         WHERE vehicle_id = ? AND status = "published"',
        [(int) $vehicleId]
    );

    return [
        'average_rating' => $stats && $stats['average_rating'] !== null ? (float) $stats['average_rating'] : 0,
        'review_count' => $stats ? (int) $stats['review_count'] : 0,
    ];
}

function site_review_stats()
{
    $stats = db_one(
        'SELECT ROUND(AVG(rating), 1) AS average_rating, COUNT(*) AS review_count
         FROM site_reviews
         WHERE status = "published"'
    );

    return [
        'average_rating' => $stats && $stats['average_rating'] !== null ? (float) $stats['average_rating'] : 0,
        'review_count' => $stats ? (int) $stats['review_count'] : 0,
    ];
}

function user_can_rate_site($userId)
{
    return !db_one('SELECT id FROM site_reviews WHERE user_id = ? LIMIT 1', [(int) $userId]);
}

function rental_reminders_for_user($userId)
{
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    return db_all(
        'SELECT b.*, v.name AS vehicle_name,
                CASE
                    WHEN b.start_date BETWEEN ? AND ? THEN "upcoming"
                    WHEN b.end_date = ? THEN "return_due"
                    WHEN b.end_date < ? AND b.status IN ("approved", "confirmed") THEN "overdue"
                    ELSE "info"
                END AS reminder_type
         FROM bookings b
         JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.user_id = ?
           AND b.status IN ("approved", "confirmed")
           AND (
                b.start_date BETWEEN ? AND ?
                OR b.end_date <= ?
           )
         ORDER BY b.start_date ASC, b.end_date ASC
         LIMIT 10',
        [$today, $tomorrow, $today, $today, (int) $userId, $today, $tomorrow, $today]
    );
}

function rating_text($averageRating, $reviewCount)
{
    if ((int) $reviewCount < 1) {
        return 'No reviews yet';
    }

    return number_format((float) $averageRating, 1) . '/5 from ' . (int) $reviewCount . ' review(s)';
}

function user_can_review_booking($booking)
{
    if (!$booking) {
        return false;
    }

    if (!in_array($booking['status'], ['approved', 'confirmed', 'completed'], true)) {
        return false;
    }

    if (strtotime($booking['end_date']) > strtotime(date('Y-m-d'))) {
        return false;
    }

    if (($booking['payment_status'] ?? '') === 'paid') {
        return true;
    }

    $paid = db_one('SELECT id FROM payments WHERE booking_id = ? AND status = "paid" LIMIT 1', [(int) $booking['id']]);
    return $paid !== null;
}

function save_upload($field, $folder, $dbPrefix = '')
{
    if (empty($_FILES[$field]['name'])) {
        return '';
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    if ($_FILES[$field]['size'] > MAX_UPLOAD_SIZE) {
        return '';
    }

    $name = $_FILES[$field]['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    if (!in_array($ext, $allowed, true)) {
        return '';
    }

    $newName = uniqid('file_', true) . '.' . $ext;
    $target = $folder . $newName;
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    move_uploaded_file($_FILES[$field]['tmp_name'], $target);
    return $dbPrefix . $newName;
}

function vehicle_image_src($image)
{
    if (!$image) {
        return '';
    }

    $image = str_replace('\\', '/', $image);

    if (strpos($image, 'uploads/') === 0) {
        return $image;
    }

    return 'uploads/vehicles/' . $image;
}

function create_otp($userId, $purpose)
{
    $last = db_one(
        'SELECT * FROM otp_codes WHERE user_id = ? AND purpose = ? ORDER BY id DESC LIMIT 1',
        [$userId, $purpose]
    );

    if ($last && strtotime($last['last_sent_at']) > time() - OTP_RESEND_SECONDS) {
        return ['error' => 'Please wait before resending OTP.'];
    }

    $code = (string) random_int(100000, 999999);
    $expires = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRE_MINUTES . ' minutes'));

    db_run(
        'INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at, last_sent_at)
         VALUES (?, ?, ?, ?, NOW())',
        [$userId, $code, $purpose, $expires]
    );

    return ['code' => $code, 'expires_at' => $expires];
}

function verify_otp_code($userId, $code, $purpose)
{
    $otp = db_one(
        'SELECT * FROM otp_codes
         WHERE user_id = ? AND otp_code = ? AND purpose = ? AND is_used = 0 AND expires_at >= NOW()
         ORDER BY id DESC LIMIT 1',
        [$userId, $code, $purpose]
    );

    if (!$otp) {
        return false;
    }

    db_run('UPDATE otp_codes SET is_used = 1 WHERE id = ?', [$otp['id']]);
    return true;
}
