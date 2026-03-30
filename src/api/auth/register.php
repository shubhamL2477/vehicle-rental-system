<?php
header('Content-Type: application/json');

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => false, "message" => "Only POST method allowed"]);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Check DB connection
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(["status" => false, "message" => "DB connection not available"]);
    exit;
}

// Read input
$raw = file_get_contents('php://input');
$data = [];

// If JSON
if (!empty($raw) && isset($_SERVER['CONTENT_TYPE']) &&
    stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}

// If normal form POST
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

function getValue($arr, $key) {
    return isset($arr[$key]) ? trim($arr[$key]) : '';
}

// Extract fields
$first_name   = getValue($data, 'first_name');
$last_name    = getValue($data, 'last_name');
$username     = getValue($data, 'username');
$email        = getValue($data, 'email');
$phone        = getValue($data, 'phone');
$address      = getValue($data, 'address');
$dob          = getValue($data, 'dob');
$password_raw = getValue($data, 'password');
$account_type = strtolower(getValue($data, 'account_type'));

if ($account_type !== 'user' && $account_type !== 'company') {
    $account_type = 'user';
}

// Validation
$errors = [];

if ($first_name === '') $errors[] = "First name is required";
if ($last_name === '') $errors[] = "Last name is required";
if ($username === '') $errors[] = "Username is required";
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
if (strlen($password_raw) < 6) $errors[] = "Password must be at least 6 characters";

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        "status" => false,
        "message" => "Validation failed",
        "errors" => $errors
    ]);
    exit;
}

try {

    // Get role id
    $role_id = 1;
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ? LIMIT 1");
    $stmt->execute([$account_type]);
    $row = $stmt->fetch();

    if ($row && isset($row['id'])) {
        $role_id = (int)$row['id'];
    }

    // Check duplicate email/username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$email, $username]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["status" => false, "message" => "Email or Username already exists"]);
        exit;
    }

    // Insert user
    $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);
    $full_name = "$first_name $last_name";
    $dob_value = ($dob === '') ? null : $dob;

    $sql = "INSERT INTO users 
        (first_name, last_name, role_id, username, full_name, email, phone, address, dob, password, status, email_verified) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0)";

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $first_name,
        $last_name,
        $role_id,
        $username,
        $full_name,
        $email,
        $phone,
        $address,
        $dob_value,
        $password_hashed
    ]);

    if (!$ok) {
        http_response_code(500);
        echo json_encode(["status" => false, "message" => "Failed to create user"]);
        exit;
    }

    $new_user_id = $pdo->lastInsertId();

    echo json_encode([
        "status" => true,
        "message" => "Registration successful",
        "data" => [
            "user_id" => (int)$new_user_id,
            "role_id" => (int)$role_id,
            "email" => $email
        ]
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Database error",
        "error" => $e->getMessage()
    ]);
    exit;
}
?>