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

// Get room ID from request
$data = json_decode(file_get_contents('php://input'), true);
$room_id = $data['room_id'] ?? null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if room exists and user is a player
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ? AND (player1_id = ? OR player2_id = ?)");
    $stmt->execute([$room_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found or you are not a player']);
        exit;
    }

    // If room has two players, update status to playing
    if ($room['player1_id'] && $room['player2_id']) {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'playing' WHERE room_id = ?");
        $stmt->execute([$room_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Waiting for opponent']);
    }

} catch (PDOException $e) {
    error_log("Database error in update_room_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in update_room_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 