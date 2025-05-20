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

$room_id = $_GET['room_id'] ?? null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get room state
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u1.username as player1_name,
            u2.username as player2_name
        FROM rooms r
        LEFT JOIN users u1 ON r.player1_id = u1.id
        LEFT JOIN users u2 ON r.player2_id = u2.id
        WHERE r.room_id = ?
    ");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    // Debug logging
    error_log('Session user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
    error_log('Room player1_id: ' . (isset($room['player1_id']) ? $room['player1_id'] : 'not set'));
    error_log('Room player2_id: ' . (isset($room['player2_id']) ? $room['player2_id'] : 'not set'));

    // Check if user is a player in this room
    if (
        $room['player1_id'] != $_SESSION['user_id'] && 
        (!$room['player2_id'] || $room['player2_id'] != $_SESSION['user_id'])
    ) {
        echo json_encode(['success' => false, 'message' => 'You are not a player in this room']);
        exit;
    }

    // If room is waiting and has two players, update status to playing
    if ($room['status'] === 'waiting' && $room['player2_id']) {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'playing' WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $room['status'] = 'playing';
    }

    // Prepare game state response
    $game_state = [
        'room_id' => $room['room_id'],
        'status' => $room['status'],
        'player1_id' => $room['player1_id'],
        'player2_id' => $room['player2_id'],
        'player1_name' => $room['player1_name'],
        'player2_name' => $room['player2_name'],
        'player1_score' => $room['player1_score'],
        'player2_score' => $room['player2_score'],
        'player1_choice' => $room['player1_choice'],
        'player2_choice' => $room['player2_choice'],
        'round_complete' => $room['round_complete'],
        'round' => isset($room['round']) ? (int)$room['round'] : 1,
        'current_user_id' => $_SESSION['user_id'],
        'rematch' => $room['rematch'] ?? false,
        'opponent_rematch' => ($room['player1_id'] == $_SESSION['user_id'] ? $room['player2_rematch'] : $room['player1_rematch']) ?? false
    ];

    echo json_encode([
        'success' => true,
        'game_state' => $game_state
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_game_state.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in get_game_state.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 