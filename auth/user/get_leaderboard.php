<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Get top players by highest winstreak achieved
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            gs.wins,
            gs.losses,
            gs.draws,
            gs.winstreak as current_winstreak,
            gs.highest_winstreak
        FROM game_stats gs
        JOIN users u ON gs.user_id = u.id
        ORDER BY gs.highest_winstreak DESC, gs.wins DESC
        LIMIT 10
    ");
    $stmt->execute();
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 