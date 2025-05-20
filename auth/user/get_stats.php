<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT wins, losses, draws FROM game_stats WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stats) {
        // Create new stats record if none exists
        $stmt = $pdo->prepare("INSERT INTO game_stats (user_id) VALUES (?)");
        $stmt->execute([$_SESSION['user_id']]);
        $stats = ['wins' => 0, 'losses' => 0, 'draws' => 0];
    }

    echo json_encode(['success' => true, 'stats' => $stats]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?> 