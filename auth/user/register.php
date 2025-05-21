<?php
header('Content-Type: application/json');
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// Log the registration attempt
error_log("Registration attempt - Username: " . $username);

// Validate input
if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please fill in all fields',
        'error_code' => 'EMPTY_FIELDS'
    ]);
    exit;
}

if (strlen($username) < 3) {
    echo json_encode([
        'success' => false, 
        'message' => 'Username must be at least 3 characters long',
        'error_code' => 'USERNAME_TOO_SHORT'
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'success' => false, 
        'message' => 'Password must be at least 6 characters long',
        'error_code' => 'PASSWORD_TOO_SHORT'
    ]);
    exit;
}

try {
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        error_log("Registration failed - Username already exists: " . $username);
        echo json_encode([
            'success' => false, 
            'message' => 'Username already exists',
            'error_code' => 'USERNAME_EXISTS'
        ]);
        exit;
    }

    // Hash password and insert user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hashedPassword]);

    error_log("Registration successful for user: " . $username);
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful'
    ]);
} catch(PDOException $e) {
    error_log("Database error during registration: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'error_code' => 'DB_ERROR',
        'debug_message' => $e->getMessage()
    ]);
}
?> 