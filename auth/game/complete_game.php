<?php
session_start();
require_once '../user/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$room_id = $data['room_id'] ?? null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get room state
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    // Check if game is actually complete (someone has 3 wins)
    if ($room['player1_score'] < 3 && $room['player2_score'] < 3) {
        echo json_encode(['success' => false, 'message' => 'Game is not complete yet']);
        exit;
    }

    // Determine winner
    $winner_id = null;
    $loser_id = null;
    if ($room['player1_score'] >= 3) {
        $winner_id = $room['player1_id'];
        $loser_id = $room['player2_id'];
    } else {
        $winner_id = $room['player2_id'];
        $loser_id = $room['player1_id'];
    }

    // Update stats for winner
    $stmt = $pdo->prepare("
        UPDATE game_stats 
        SET wins = wins + 1,
            winstreak = winstreak + 1,
            highest_winstreak = GREATEST(highest_winstreak, winstreak + 1)
        WHERE user_id = ?
    ");
    $stmt->execute([$winner_id]);

    // Update stats for loser
    $stmt = $pdo->prepare("
        UPDATE game_stats 
        SET losses = losses + 1,
            winstreak = 0
        WHERE user_id = ?
    ");
    $stmt->execute([$loser_id]);

    // Update room status to completed
    $stmt = $pdo->prepare("UPDATE rooms SET status = 'completed' WHERE room_id = ?");
    $stmt->execute([$room_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Database error in complete_game.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 