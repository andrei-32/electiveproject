<?php
session_start();
require_once '../user/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$result = $data['result'] ?? ''; // 'win' or 'loss'

if (!in_array($result, ['win', 'loss'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid result']);
    exit;
}

try {
    if ($result === 'win') {
        // Increment winstreak on win
        $stmt = $pdo->prepare("UPDATE users SET winstreak = winstreak + 1 WHERE id = ?");
    } else {
        // Reset winstreak on loss
        $stmt = $pdo->prepare("UPDATE users SET winstreak = 0 WHERE id = ?");
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 