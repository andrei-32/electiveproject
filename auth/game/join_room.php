<?php
session_start();
require_once '../user/config.php';

header('Content-Type: application/json');

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

    // Check if room exists and is waiting for player
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ? AND status = 'waiting'");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found or game already in progress']);
        exit;
    }

    // Check if user is not trying to join their own room
    if ($room['player1_id'] == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot join your own room']);
        exit;
    }

    // Update room with player2
    $stmt = $pdo->prepare("UPDATE rooms SET player2_id = ?, status = 'playing' WHERE room_id = ?");
    $stmt->execute([$_SESSION['user_id'], $room_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to join room']);
}
?> 