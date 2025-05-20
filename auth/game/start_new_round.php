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

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$room_id = $data['room_id'] ?? null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Only allow if user is a player in the room
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ? AND (player1_id = ? OR player2_id = ?)");
    $stmt->execute([$room_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found or you are not a player']);
        exit;
    }

    // Reset choices, round_complete, and increment round for new round
    $stmt = $pdo->prepare("UPDATE rooms SET player1_choice = NULL, player2_choice = NULL, round_complete = FALSE, round = IFNULL(round, 1) + 1 WHERE room_id = ?");
    $stmt->execute([$room_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Database error in start_new_round.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in start_new_round.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 