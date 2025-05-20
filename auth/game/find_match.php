<?php
session_start();
header('Content-Type: application/json');
require_once '../user/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First, try to find an available room
    $stmt = $pdo->prepare("SELECT room_id FROM rooms WHERE status = 'waiting' AND player1_id != ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($room) {
        // Join existing room
        $stmt = $pdo->prepare("UPDATE rooms SET player2_id = ?, status = 'playing' WHERE room_id = ?");
        $stmt->execute([$_SESSION['user_id'], $room['room_id']]);
        
        echo json_encode([
            'success' => true,
            'room_id' => $room['room_id']
        ]);
    } else {
        // Create new room
        do {
            $room_id = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_id = ?");
            $stmt->execute([$room_id]);
        } while ($stmt->fetchColumn() > 0);

        $stmt = $pdo->prepare("INSERT INTO rooms (room_id, player1_id, status) VALUES (?, ?, 'waiting')");
        $stmt->execute([$room_id, $_SESSION['user_id']]);

        echo json_encode([
            'success' => true,
            'room_id' => $room_id
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to find match']);
}
?> 