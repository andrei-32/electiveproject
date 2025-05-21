<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// Log the login attempt
error_log("Login attempt - Username: " . $username);

if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please fill in all fields',
        'error_code' => 'EMPTY_FIELDS'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        error_log("Login failed - Username not found: " . $username);
        echo json_encode([
            'success' => false, 
            'message' => 'Username not found',
            'error_code' => 'USER_NOT_FOUND'
        ]);
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        error_log("Login failed - Invalid password for user: " . $username);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid password',
            'error_code' => 'INVALID_PASSWORD'
        ]);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    error_log("Login successful for user: " . $username);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    error_log("Database error during login: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'error_code' => 'DB_ERROR',
        'debug_message' => $e->getMessage()
    ]);
}
?> 