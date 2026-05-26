<?php

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

    if (
        strpos($image, 'uploads/') === 0
        || strpos($image, 'assets/images/') === 0
        || preg_match('#^https?://#', $image)
    ) {
        return $image;
    }

    return 'uploads/vehicles/' . $image;
}
