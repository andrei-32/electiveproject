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
$choice = $data['choice'] ?? null;

if (!$room_id || !$choice) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
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

    // Determine if user is player1 or player2
    $is_player1 = $room['player1_id'] == $_SESSION['user_id'];
    $player_field = $is_player1 ? 'player1_choice' : 'player2_choice';
    $opponent_field = $is_player1 ? 'player2_choice' : 'player1_choice';

    // Check if player has already made a choice
    if (!empty($room[$player_field])) {
        echo json_encode(['success' => false, 'message' => 'You have already made your choice for this round']);
        exit;
    }

    // Check if round is already complete
    if ($room['round_complete']) {
        echo json_encode(['success' => false, 'message' => 'This round is already complete']);
        exit;
    }

    // Update player's choice
    $stmt = $pdo->prepare("UPDATE rooms SET $player_field = ? WHERE room_id = ?");
    $stmt->execute([$choice, $room_id]);

    // Get opponent's username
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$is_player1 ? $room['player2_id'] : $room['player1_id']]);
    $opponent = $stmt->fetch(PDO::FETCH_ASSOC);

    // Trigger Pusher event for opponent's move
    $pusher->trigger("game-room-{$room_id}", 'opponent-move', [
        'choice' => $choice,
        'username' => $_SESSION['username']
    ]);

    // Check if both players have made their choices
    $stmt = $pdo->prepare("SELECT player1_choice, player2_choice FROM rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $moves = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($moves['player1_choice']) && !empty($moves['player2_choice'])) {
        // Both players have made their choices, determine winner
        $winner = determineWinner($moves['player1_choice'], $moves['player2_choice']);

        // Update scores and mark round as complete
        if ($winner === 'player1') {
            $stmt = $pdo->prepare("UPDATE rooms SET player1_score = player1_score + 1, round_complete = TRUE WHERE room_id = ?");
        } elseif ($winner === 'player2') {
            $stmt = $pdo->prepare("UPDATE rooms SET player2_score = player2_score + 1, round_complete = TRUE WHERE room_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE rooms SET round_complete = TRUE WHERE room_id = ?");
        }
        $stmt->execute([$room_id]);

        // Get updated game state
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $gameState = $stmt->fetch(PDO::FETCH_ASSOC);

        // Trigger Pusher event for game state update
        $pusher->trigger("game-room-{$room_id}", 'game-state-update', [
            'player1_score' => $gameState['player1_score'],
            'player2_score' => $gameState['player2_score'],
            'round' => $gameState['round'] ?? 1,
            'status' => $gameState['status'],
            'winner' => $winner,
            'player1_choice' => $gameState['player1_choice'],
            'player2_choice' => $gameState['player2_choice']
        ]);

        // --- NEW: If game is complete (3 wins), update stats and trigger game-complete event ---
        error_log('Checking for game completion: P1=' . $gameState['player1_score'] . ' P2=' . $gameState['player2_score']);
        if ($gameState['player1_score'] >= 3 || $gameState['player2_score'] >= 3) {
            $winner_id = $gameState['player1_score'] >= 3 ? $gameState['player1_id'] : $gameState['player2_id'];
            $loser_id = $gameState['player1_score'] >= 3 ? $gameState['player2_id'] : $gameState['player1_id'];
            $winner_score = $gameState['player1_score'] >= 3 ? $gameState['player1_score'] : $gameState['player2_score'];
            $loser_score = $gameState['player1_score'] >= 3 ? $gameState['player2_score'] : $gameState['player1_score'];

            error_log('Game complete! Winner: ' . $winner_id . ' Loser: ' . $loser_id);

            // Update stats for winner
            $stmt = $pdo->prepare("UPDATE game_stats SET wins = wins + 1, winstreak = winstreak + 1, highest_winstreak = GREATEST(highest_winstreak, winstreak + 1) WHERE user_id = ?");
            if (!$stmt->execute([$winner_id])) {
                error_log('Failed to update winner stats: ' . print_r($stmt->errorInfo(), true));
            }

            // Update stats for loser
            $stmt = $pdo->prepare("UPDATE game_stats SET losses = losses + 1, winstreak = 0 WHERE user_id = ?");
            if (!$stmt->execute([$loser_id])) {
                error_log('Failed to update loser stats: ' . print_r($stmt->errorInfo(), true));
            }

            // Trigger Pusher event for game completion
            $pusher->trigger("game-room-{$room_id}", 'game-complete', [
                'winner_id' => $winner_id,
                'loser_id' => $loser_id,
                'winner_score' => $winner_score,
                'loser_score' => $loser_score
            ]);
        }
        // --- END NEW ---
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Database error in make_move.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in make_move.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

function determineWinner($player1, $player2) {
    if ($player1 === $player2) return 'tie';
    
    $winning_combos = [
        'rock' => 'scissors',
        'paper' => 'rock',
        'scissors' => 'paper'
    ];
    
    return $winning_combos[$player1] === $player2 ? 'player1' : 'player2';
}
?> 