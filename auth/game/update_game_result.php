<?php
session_start();
require_once '../user/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$room_id = $data['room_id'] ?? '';
$result = $data['result'] ?? ''; // 'win', 'loss', or 'tie'

if (!in_array($result, ['win', 'loss', 'tie'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid result']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Update winstreak based on result
    if ($result === 'win') {
        $stmt = $pdo->prepare("UPDATE users SET winstreak = winstreak + 1 WHERE id = ?");
    } else if ($result === 'loss') {
        $stmt = $pdo->prepare("UPDATE users SET winstreak = 0 WHERE id = ?");
    }
    // For ties, we don't modify the winstreak
    if ($result !== 'tie') {
        $stmt->execute([$_SESSION['user_id']]);
    }

    // Update game stats
    $column = $result . 's'; // becomes 'wins', 'losses', or 'ties'
    $stmt = $pdo->prepare("
        INSERT INTO game_stats (user_id, $column, total_games) 
        VALUES (?, 1, 1)
        ON DUPLICATE KEY UPDATE 
        $column = $column + 1,
        total_games = total_games + 1
    ");
    $stmt->execute([$_SESSION['user_id']]);

    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 