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

function vehicle_filter_options($input)
{
    $search = trim((string) ($input['search'] ?? $input['location'] ?? ''));
    $location = trim((string) ($input['location'] ?? ''));
    $startDate = trim((string) ($input['start_date'] ?? ''));
    $endDate = trim((string) ($input['end_date'] ?? ''));
    $categoryId = (int) ($input['category_id'] ?? 0);
    $typeId = (int) ($input['type_id'] ?? 0);
    $typeName = trim((string) ($input['vehicle_type'] ?? $input['type'] ?? ''));
    $driverPreference = trim((string) ($input['driver_preference'] ?? ''));
    $minPrice = (float) ($input['min_price'] ?? 0);
    $maxPrice = (float) ($input['max_price'] ?? $input['budget'] ?? 0);

    if ($typeId < 1 && $typeName !== '') {
        $type = db_one('SELECT id, category_id FROM vehicle_types WHERE LOWER(name) LIKE LOWER(?) LIMIT 1', ['%' . $typeName . '%']);
        if ($type) {
            $typeId = (int) $type['id'];
            $categoryId = $categoryId > 0 ? $categoryId : (int) $type['category_id'];
        } else {
            $category = db_one('SELECT id FROM vehicle_categories WHERE LOWER(name) LIKE LOWER(?) LIMIT 1', ['%' . $typeName . '%']);
            if ($category) {
                $categoryId = (int) $category['id'];
            }
        }
    }

    return [
        'search' => $search,
        'location' => $location,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'category_id' => $categoryId,
        'type_id' => $typeId,
        'min_price' => max(0, $minPrice),
        'max_price' => max(0, $maxPrice),
        'driver_preference' => in_array($driverPreference, ['self_drive', 'with_driver'], true) ? $driverPreference : '',
    ];
}

function vehicle_filter_sql($filters)
{
    $params = [];
    $where = 'WHERE v.status = "available"';
    $startDate = $filters['start_date'];
    $endDate = $filters['end_date'];

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

    if ($filters['search'] !== '') {
        $where .= ' AND (v.name LIKE ? OR COALESCE(NULLIF(u.company_name, ""), u.name) LIKE ? OR v.location LIKE ?)';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    if ($filters['location'] !== '') {
        $where .= ' AND v.location LIKE ?';
        $params[] = '%' . $filters['location'] . '%';
    }

    if ($filters['category_id'] > 0) {
        $where .= ' AND v.category_id = ?';
        $params[] = $filters['category_id'];
    }

    if ($filters['type_id'] > 0) {
        $where .= ' AND v.type_id = ?';
        $params[] = $filters['type_id'];
    }

    if ($filters['min_price'] > 0) {
        $where .= ' AND v.self_drive_price >= ?';
        $params[] = $filters['min_price'];
    }

    if ($filters['max_price'] > 0) {
        $priceColumn = $filters['driver_preference'] === 'with_driver' ? 'v.with_driver_price' : 'v.self_drive_price';
        $where .= ' AND ' . $priceColumn . ' <= ?';
        $params[] = $filters['max_price'];
    }

    return [$where, $params];
}

function filtered_vehicles($input, $limit = 50)
{
    $filters = vehicle_filter_options($input);
    [$where, $params] = vehicle_filter_sql($filters);
    $limit = max(1, min(100, (int) $limit));

    $vehicles = db_all(
        'SELECT v.id, v.company_id, v.category_id, v.type_id, v.name, v.location,
                v.self_drive_price, v.with_driver_price, v.description, v.status,
                v.latitude, v.longitude, v.image, v.created_at,
                COALESCE(NULLIF(u.company_name, ""), u.name) AS company_name,
                c.name AS category_name, t.name AS type_name,
                (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE vehicle_id = v.id AND status = "published") AS average_rating,
                (SELECT COUNT(*) FROM reviews WHERE vehicle_id = v.id AND status = "published") AS review_count
         FROM vehicles v
         JOIN users u ON u.id = v.company_id
         JOIN vehicle_categories c ON c.id = v.category_id
         JOIN vehicle_types t ON t.id = v.type_id
         ' . $where . '
         ORDER BY v.id DESC
         LIMIT ' . $limit,
        $params
    );

    return [$vehicles, $filters];
}

function vehicle_consultant_questions()
{
    return [
        'What is your daily budget?',
        'Which vehicle type do you prefer?',
        'Which location should I search near?',
        'What rental start and end dates do you need?',
        'How many seats do you need?',
        'Do you prefer self-drive or with driver?',
    ];
}

function vehicle_consultant_missing_fields($input, $filters)
{
    $missing = [];

    if ((float) ($input['budget'] ?? $filters['max_price'] ?? 0) <= 0) {
        $missing[] = 'budget';
    }

    if (trim((string) ($input['vehicle_type'] ?? $input['type'] ?? '')) === '' && (int) ($filters['category_id'] ?? 0) < 1 && (int) ($filters['type_id'] ?? 0) < 1) {
        $missing[] = 'vehicle type';
    }

    if (($filters['location'] ?? '') === '') {
        $missing[] = 'location';
    }

    if (($filters['start_date'] ?? '') === '' || ($filters['end_date'] ?? '') === '') {
        $missing[] = 'dates';
    }

    if ((int) ($input['seats'] ?? 0) < 1) {
        $missing[] = 'seat count';
    }

    if (($filters['driver_preference'] ?? '') === '') {
        $missing[] = 'driver preference';
    }

    return $missing;
}

function vehicle_consultant_local_recommendations($vehicles, $filters, $input)
{
    $driverPreference = $filters['driver_preference'] === 'with_driver' ? 'with_driver' : 'self_drive';
    $budget = (float) ($input['budget'] ?? $filters['max_price'] ?? 0);
    $recommendations = [];

    foreach ($vehicles as $vehicle) {
        $price = $driverPreference === 'with_driver' ? (float) $vehicle['with_driver_price'] : (float) $vehicle['self_drive_price'];
        $reasons = [];
        $reasons[] = 'Matches available vehicle filters for the selected dates.';

        if ($filters['location'] !== '' && stripos($vehicle['location'], $filters['location']) !== false) {
            $reasons[] = 'Location matches ' . $vehicle['location'] . '.';
        }

        if ($budget > 0 && $price <= $budget) {
            $reasons[] = 'Fits the daily budget at ' . money($price) . '.';
        }

        if ($driverPreference === 'with_driver') {
            $reasons[] = 'Supports booking with driver.';
        } else {
            $reasons[] = 'Good self-drive option.';
        }

        if ((int) ($vehicle['review_count'] ?? 0) > 0) {
            $reasons[] = rating_text($vehicle['average_rating'], $vehicle['review_count']) . '.';
        }

        $recommendations[] = [
            'id' => (int) $vehicle['id'],
            'name' => (string) $vehicle['name'],
            'company_name' => (string) $vehicle['company_name'],
            'location' => (string) $vehicle['location'],
            'category_name' => (string) $vehicle['category_name'],
            'type_name' => (string) $vehicle['type_name'],
            'self_drive_price' => (float) $vehicle['self_drive_price'],
            'with_driver_price' => (float) $vehicle['with_driver_price'],
            'image' => vehicle_image_src($vehicle['image'] ?? ''),
            'url' => 'vehicle.php?id=' . (int) $vehicle['id'],
            'explanation' => implode(' ', $reasons),
        ];

        if (count($recommendations) >= 5) {
            break;
        }
    }

    return $recommendations;
}

function vehicle_consultant_fallback_answer($recommendations, $missingFields)
{
    if ($missingFields) {
        return 'I can recommend better matches if you also share: ' . implode(', ', $missingFields) . '. I still checked the available vehicle list with the details you entered.';
    }

    if (!$recommendations) {
        return 'I could not find a matching available vehicle for those details. Try a wider budget, nearby location, different vehicle type, or another date range.';
    }

    return 'I found ' . count($recommendations) . ' available vehicle match(es). The top options below are ranked by your budget, location, dates, and driver preference.';
}

function ai_json_from_text($text)
{
    $text = trim((string) $text);
    $decoded = json_decode($text, true);

    if (is_array($decoded)) {
        return $decoded;
    }

    $start = strpos($text, '{');
    $end = strrpos($text, '}');

    if ($start === false || $end === false || $end <= $start) {
        return null;
    }

    $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
    return is_array($decoded) ? $decoded : null;
}

function groq_chat_json($messages)
{
    if (GROQ_API_KEY === '' || !function_exists('curl_init')) {
        return null;
    }

    $payload = [
        'model' => GROQ_MODEL,
        'messages' => $messages,
        'temperature' => 0.3,
        'max_completion_tokens' => 700,
    ];

    $curl = curl_init(GROQ_API_URL);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . GROQ_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($response === false || $status >= 400) {
        db_log_error(new RuntimeException('Groq API request failed: HTTP ' . $status . ' ' . $error), 'groq_chat_json');
        return null;
    }

    $json = json_decode($response, true);
    $content = $json['choices'][0]['message']['content'] ?? '';

    return ai_json_from_text($content);
}

function vehicle_consultant_ai_response($input, $filters, $recommendations, $missingFields)
{
    $vehiclesForPrompt = array_map(function ($vehicle) use ($filters) {
        $driverPreference = $filters['driver_preference'] === 'with_driver' ? 'with_driver' : 'self_drive';
        $price = $driverPreference === 'with_driver' ? $vehicle['with_driver_price'] : $vehicle['self_drive_price'];

        return [
            'id' => $vehicle['id'],
            'name' => $vehicle['name'],
            'company_name' => $vehicle['company_name'],
            'location' => $vehicle['location'],
            'type' => trim($vehicle['category_name'] . ' ' . $vehicle['type_name']),
            'daily_price' => $price,
            'rating' => rating_text($vehicle['average_rating'] ?? 0, $vehicle['review_count'] ?? 0),
            'local_explanation' => $vehicle['explanation'],
        ];
    }, $recommendations);

    $messages = [
        [
            'role' => 'system',
            'content' => 'You are Hyrox Rental vehicle consulting agent. Use only the provided vehicle data. Do not invent vehicles, prices, dates, or availability. If details are missing, ask for them clearly. Return only JSON with keys: answer, note, recommendations. recommendations must be an array of objects with id and explanation.',
        ],
        [
            'role' => 'user',
            'content' => json_encode([
                'customer_message' => (string) ($input['message'] ?? ''),
                'preferences' => [
                    'budget' => $input['budget'] ?? '',
                    'vehicle_type' => $input['vehicle_type'] ?? ($input['type'] ?? ''),
                    'location' => $input['location'] ?? '',
                    'start_date' => $input['start_date'] ?? '',
                    'end_date' => $input['end_date'] ?? '',
                    'seats' => $input['seats'] ?? '',
                    'driver_preference' => $input['driver_preference'] ?? '',
                ],
                'filters_used' => $filters,
                'missing_fields' => $missingFields,
                'available_vehicle_matches' => $vehiclesForPrompt,
            ]),
        ],
    ];

    return groq_chat_json($messages);
}

function vehicle_consultant_recommendations($input)
{
    [$vehicles, $filters] = filtered_vehicles($input, 12);
    $missingFields = vehicle_consultant_missing_fields($input, $filters);
    $recommendations = vehicle_consultant_local_recommendations($vehicles, $filters, $input);
    $answer = vehicle_consultant_fallback_answer($recommendations, $missingFields);
    $note = 'Recommendations use the vehicle filter API and live availability. Seat count is collected for advice, but this database currently has no seat-capacity field.';
    $aiUsed = false;

    $ai = vehicle_consultant_ai_response($input, $filters, $recommendations, $missingFields);

    if (is_array($ai)) {
        if (!empty($ai['answer']) && is_string($ai['answer'])) {
            $answer = trim($ai['answer']);
            $aiUsed = true;
        }

        if (!empty($ai['note']) && is_string($ai['note'])) {
            $note = trim($ai['note']);
        }

        if (!empty($ai['recommendations']) && is_array($ai['recommendations'])) {
            $aiExplanations = [];
            foreach ($ai['recommendations'] as $item) {
                if (isset($item['id'], $item['explanation'])) {
                    $aiExplanations[(int) $item['id']] = trim((string) $item['explanation']);
                }
            }

            foreach ($recommendations as &$recommendation) {
                if (isset($aiExplanations[$recommendation['id']]) && $aiExplanations[$recommendation['id']] !== '') {
                    $recommendation['explanation'] = $aiExplanations[$recommendation['id']];
                    $aiUsed = true;
                }
            }
            unset($recommendation);
        }
    }

    return [
        'questions' => vehicle_consultant_questions(),
        'filters' => $filters,
        'missing_fields' => $missingFields,
        'recommendations' => $recommendations,
        'answer' => $answer,
        'note' => $note,
        'ai_provider' => $aiUsed ? 'Groq' : 'Local fallback',
        'ai_model' => GROQ_MODEL,
        'ai_used' => $aiUsed,
    ];
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
