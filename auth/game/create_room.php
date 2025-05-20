<?php
session_start();
require_once '../user/config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

try {
    // Test database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Generate a unique 6-character room ID
    do {
        $room_id = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_id = ?");
        $stmt->execute([$room_id]);
    } while ($stmt->fetchColumn() > 0);
    
    // Create new room
    $stmt = $pdo->prepare("INSERT INTO rooms (room_id, player1_id, status) VALUES (?, ?, 'waiting')");
    $result = $stmt->execute([$room_id, $_SESSION['user_id']]);

    if (!$result) {
        throw new Exception("Failed to insert room into database");
    }

    echo json_encode([
        'success' => true,
        'room_id' => $room_id
    ]);

} catch (PDOException $e) {
    error_log("Database error in create_room.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in create_room.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 