<?php
session_start();
require_once '../user/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$room_id = $data['room_id'] ?? null;
$action = $data['action'] ?? 'request'; // 'request', 'accept', or 'decline'

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    $isPlayer1 = $room['player1_id'] == $_SESSION['user_id'];
    $rematchField = $isPlayer1 ? 'player1_rematch' : 'player2_rematch';
    $opponentRematchField = $isPlayer1 ? 'player2_rematch' : 'player1_rematch';

    // Get usernames for notifications
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id IN (?, ?)");
    $stmt->execute([$room['player1_id'], $room['player2_id']]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $player1Username = $players[0]['username'];
    $player2Username = $players[1]['username'];

    switch ($action) {
        case 'request':
            // Set this player's rematch request
            $stmt = $pdo->prepare("UPDATE rooms SET $rematchField = TRUE WHERE room_id = ?");
            $stmt->execute([$room_id]);

            // Trigger rematch request event
            $pusher->trigger("game-room-{$room_id}", 'rematch-request', [
                'username' => $_SESSION['username'],
                'player1_username' => $player1Username,
                'player2_username' => $player2Username,
                'requesting_player' => $isPlayer1 ? 'player1' : 'player2'
            ]);
            break;

        case 'accept':
            // Check if both players requested rematch
            $stmt = $pdo->prepare("SELECT player1_rematch, player2_rematch FROM rooms WHERE room_id = ?");
            $stmt->execute([$room_id]);
            $rematch = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($rematch['player1_rematch'] && $rematch['player2_rematch']) {
                // Reset the game
                $stmt = $pdo->prepare("UPDATE rooms SET 
                    player1_score = 0, 
                    player2_score = 0, 
                    player1_choice = NULL, 
                    player2_choice = NULL, 
                    round_complete = FALSE, 
                    rematch = TRUE, 
                    player1_rematch = FALSE, 
                    player2_rematch = FALSE, 
                    status = 'playing',
                    round = 1 
                    WHERE room_id = ?");
                $stmt->execute([$room_id]);

                // Trigger rematch accepted event
                $pusher->trigger("game-room-{$room_id}", 'rematch-accepted', [
                    'player1_username' => $player1Username,
                    'player2_username' => $player2Username,
                    'status' => 'playing',
                    'round' => 1,
                    'player1_score' => 0,
                    'player2_score' => 0
                ]);

                // Also trigger game state update
                $pusher->trigger("game-room-{$room_id}", 'game-state-update', [
                    'status' => 'playing',
                    'player1_username' => $player1Username,
                    'player2_username' => $player2Username,
                    'player1_score' => 0,
                    'player2_score' => 0,
                    'round' => 1,
                    'message' => 'New game started!'
                ]);
            }
            break;

        case 'decline':
            // Reset rematch flags
            $stmt = $pdo->prepare("UPDATE rooms SET 
                player1_rematch = FALSE, 
                player2_rematch = FALSE, 
                rematch = FALSE 
                WHERE room_id = ?");
            $stmt->execute([$room_id]);

            // Trigger rematch declined event
            $pusher->trigger("game-room-{$room_id}", 'rematch-declined', [
                'username' => $_SESSION['username'],
                'player1_username' => $player1Username,
                'player2_username' => $player2Username,
                'message' => $_SESSION['username'] . ' declined the rematch'
            ]);
            break;
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Database error in rematch.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in rematch.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 