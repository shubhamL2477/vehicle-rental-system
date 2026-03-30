<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => false, "message" => "Only POST method allowed"]);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(["status" => false, "message" => "DB connection not available"]);
    exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    http_response_code(422);
    echo json_encode(["status" => false, "message" => "Username and password are required"]);
    exit;
}

try {
    // Check user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(["status" => false, "message" => "Invalid username or password"]);
        exit;
    }

    // Check password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["status" => false, "message" => "Invalid username or password"]);
        exit;
    }

    // Check account status
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(["status" => false, "message" => "Account not active"]);
        exit;
    }

    // Start session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role_id'] = $user['role_id'];

    echo json_encode([
        "status" => true,
        "message" => "Login successful",
        "data" => [
            "user_id" => (int)$user['id'],
            "username" => $user['username'],
            "role_id" => (int)$user['role_id']
        ]
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "message" => "Database error", "error" => $e->getMessage()]);
    exit;
}
?>