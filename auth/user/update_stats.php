<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$result = $data['result'] ?? '';

if (!in_array($result, ['win', 'loss', 'draw'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid result']);
    exit;
}

try {
    // Get current stats
    $stmt = $pdo->prepare("SELECT wins, losses, draws, winstreak, highest_winstreak FROM game_stats WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_stats) {
        // Create new stats record if none exists
        $stmt = $pdo->prepare("INSERT INTO game_stats (user_id) VALUES (?)");
        $stmt->execute([$_SESSION['user_id']]);
        $current_stats = ['wins' => 0, 'losses' => 0, 'draws' => 0, 'winstreak' => 0, 'highest_winstreak' => 0];
    }

    // Calculate new winstreak and highest winstreak
    $new_winstreak = $current_stats['winstreak'];
    $highest_winstreak = $current_stats['highest_winstreak'];
    
    if ($result === 'win') {
        $new_winstreak++;
        // Update highest winstreak if current winstreak is higher
        if ($new_winstreak > $highest_winstreak) {
            $highest_winstreak = $new_winstreak;
        }
    } elseif ($result === 'loss') {
        $new_winstreak = 0;
    }
    // Draw doesn't affect winstreak

    // Update stats
    $column = $result . 's';
    $stmt = $pdo->prepare("
        UPDATE game_stats 
        SET $column = $column + 1,
            total_games = total_games + 1,
            winstreak = ?,
            highest_winstreak = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$new_winstreak, $highest_winstreak, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?> 