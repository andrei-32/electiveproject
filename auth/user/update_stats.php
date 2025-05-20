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
    $column = $result . 's';
    $stmt = $pdo->prepare("UPDATE game_stats SET $column = $column + 1, total_games = total_games + 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?> 